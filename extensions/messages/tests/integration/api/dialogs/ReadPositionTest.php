<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialogs;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Messages\Dialog;
use Flarum\Messages\Dialog\Event\UserDataSaving;
use Flarum\Messages\Dialog\Event\UserRead;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\UserDialogState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * A member's read position only ever moves forward. It is their read receipt
 * too, so going back would take back a "read" the others were already told.
 */
class ReadPositionTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var UserRead[] */
    public static array $raised = [];

    protected function setUp(): void
    {
        parent::setUp();

        static::$raised = [];

        $this->extension('flarum-messages');

        $this->extend((new Extend\Event())->listen(UserRead::class, fn (UserRead $event) => static::$raised[] = $event));

        // An hour ago, so that replying isn't throttled as flooding.
        $now = Carbon::now()->subHour();

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice'],
                ['id' => 4, 'username' => 'bob'],
            ],
            Dialog::class => [
                ['id' => 101, 'type' => 'direct', 'first_message_id' => 101, 'last_message_id' => 104, 'last_message_at' => $now],
            ],
            DialogMessage::class => [
                ['id' => 101, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'One', 'number' => 1, 'created_at' => $now],
                ['id' => 102, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'Two', 'number' => 2, 'created_at' => $now],
                ['id' => 103, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'Three', 'number' => 3, 'created_at' => $now],
                ['id' => 104, 'dialog_id' => 101, 'user_id' => 4, 'content' => 'Four', 'number' => 4, 'created_at' => $now],
            ],
            'dialog_user' => [
                ['dialog_id' => 101, 'user_id' => 3, 'joined_at' => $now, 'last_read_message_id' => 101],
                ['dialog_id' => 101, 'user_id' => 4, 'joined_at' => $now, 'last_read_message_id' => 104],
            ],
        ]);
    }

    private function read(int $messageId): void
    {
        $response = $this->send($this->request('PATCH', '/api/dialogs/101', [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'dialogs', 'id' => '101', 'attributes' => ['lastReadMessageId' => $messageId]]],
        ]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    private function position(): int
    {
        return UserDialogState::query()->where('dialog_id', 101)->where('user_id', 3)->value('last_read_message_id');
    }

    #[Test]
    public function reading_further_moves_it_and_says_from_where(): void
    {
        $this->read(103);

        $this->assertSame(103, $this->position());
        $this->assertCount(1, static::$raised);
        $this->assertSame(101, static::$raised[0]->previousMessageId);
        $this->assertSame(103, static::$raised[0]->state->last_read_message_id);
    }

    #[Test]
    public function reading_less_far_leaves_it_and_raises_nothing(): void
    {
        $this->read(101);

        $this->assertSame(101, $this->position());
        $this->assertSame([], static::$raised);
    }

    #[Test]
    public function a_request_that_loaded_it_before_another_moved_it_further_does_not_take_it_back(): void
    {
        // Another tab reads to 104 after this request loaded the row at 101,
        // and before it writes 103.
        $this->app()->getContainer()->make(Dispatcher::class)->listen(UserDataSaving::class, function () {
            $this->database()->table('dialog_user')->where('dialog_id', 101)->where('user_id', 3)->update(['last_read_message_id' => 104]);
        });

        $this->read(103);

        $this->assertSame(104, $this->position());
        $this->assertSame([], static::$raised, 'Nobody is told of a move that did not happen.');
    }

    #[Test]
    public function replying_reads_up_to_the_reply(): void
    {
        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => 3,
            'json' => ['data' => ['type' => 'dialog-messages', 'attributes' => ['content' => 'Hi'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '101']]]]],
        ]));

        $this->assertEquals(201, $response->getStatusCode(), (string) $response->getBody());

        $reply = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertSame($reply, $this->position());
        $this->assertSame(101, static::$raised[0]->previousMessageId);
    }
}
