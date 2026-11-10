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
use Flarum\Messages\Dialog\Event\UserLeft;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\Tests\integration\CapturesSentMail;
use Flarum\Messages\UserDialogState;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Someone added to a group by somebody else can always get out of it. Their
 * messages stay for the others. A direct conversation can't be left: there
 * would be nobody else in it.
 */
class LeaveTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use CapturesSentMail;

    /** @var UserLeft[] */
    public static array $left = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$left = [];

        $this->extension('flarum-messages');

        $this->extend(
            (new Extend\Event())->listen(UserLeft::class, fn (UserLeft $event) => self::$left[] = $event)
        );

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
            ],
            Dialog::class => [
                ['id' => 90, 'type' => 'group', 'first_message_id' => 90, 'last_message_id' => 91, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subDay()],
                ['id' => 92, 'type' => 'direct', 'first_message_id' => 92, 'last_message_id' => 92, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
            ],
            DialogMessage::class => [
                ['id' => 90, 'dialog_id' => 90, 'user_id' => 3, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
                ['id' => 91, 'dialog_id' => 90, 'user_id' => 5, 'content' => '<t><p>Hi alice</p></t>', 'number' => 2, 'created_at' => Carbon::now()->subHour()],
                ['id' => 92, 'dialog_id' => 92, 'user_id' => 3, 'content' => '<t><p>Hi bob</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'dialog_user' => [
                ['dialog_id' => 90, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 90],
                ['dialog_id' => 90, 'user_id' => 4, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 90],
                ['dialog_id' => 90, 'user_id' => 5, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 91],
                ['dialog_id' => 92, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay(), 'last_read_message_id' => 92],
                ['dialog_id' => 92, 'user_id' => 4, 'joined_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    protected function leave(int $dialogId, ?int $actor): ResponseInterface
    {
        return $this->send($this->request('POST', "/api/dialogs/$dialogId/leave", $actor ? ['authenticatedAs' => $actor] : []));
    }

    #[Test]
    public function a_member_leaves_a_group_and_no_longer_sees_it(): void
    {
        $response = $this->leave(90, 3);

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals(404, $this->send($this->request('GET', '/api/dialogs/90', ['authenticatedAs' => 3]))->getStatusCode());
        $this->assertFalse(UserDialogState::query()->where('dialog_id', 90)->where('user_id', 3)->exists());
    }

    #[Test]
    public function the_others_keep_the_group_and_what_the_leaver_wrote_in_it(): void
    {
        $this->leave(90, 3);

        $this->assertEqualsCanonicalizing([4, 5], UserDialogState::query()->where('dialog_id', 90)->pluck('user_id')->all());
        $this->assertTrue(DialogMessage::query()->whereKey(90)->exists());
    }

    #[Test]
    public function leaving_takes_the_group_off_the_leavers_unread_count(): void
    {
        $this->leave(90, 3);

        $response = $this->send($this->request('GET', '/api/users/3', ['authenticatedAs' => 3]));

        $this->assertSame(0, json_decode((string) $response->getBody(), true)['data']['attributes']['unreadDialogCount']);
    }

    #[Test]
    public function a_member_who_left_cannot_write_in_the_group(): void
    {
        $this->leave(90, 3);

        $response = $this->send($this->request('POST', '/api/dialog-messages', [
            'authenticatedAs' => 3,
            'json' => ['data' => [
                'type' => 'dialog-messages',
                'attributes' => ['content' => 'Back again'],
                'relationships' => ['dialog' => ['data' => ['type' => 'dialogs', 'id' => '90']]],
            ]],
        ]));

        $this->assertEquals(404, $response->getStatusCode());
    }

    #[Test]
    public function leaving_is_announced(): void
    {
        $this->leave(90, 3);

        $this->assertCount(1, self::$left);
        $this->assertEquals(90, self::$left[0]->dialog->id);
        $this->assertEquals(3, self::$left[0]->user->id);
    }

    #[Test]
    public function the_others_are_told_in_the_conversation_who_left(): void
    {
        $this->leave(90, 3);

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 5])->withQueryParams(['filter' => ['dialog' => '90']])
        );

        $messages = collect(json_decode((string) $response->getBody(), true)['data'])->sortBy('attributes.number')->values();
        $notice = $messages->last();

        $this->assertCount(3, $messages);
        $this->assertEquals('comment', $messages->first()['attributes']['type']);
        $this->assertEquals('userLeft', $notice['attributes']['type']);
        $this->assertEquals('3', $notice['relationships']['user']['data']['id']);
        $this->assertNull($notice['attributes']['contentHtml'] ?? null);

        // The end of the conversation, where its stream stops.
        $this->assertEquals($notice['id'], (string) Dialog::query()->findOrFail(90)->last_message_id);
    }

    #[Test]
    public function someone_leaving_is_nothing_new_to_read(): void
    {
        $this->captureSentMail();

        $this->leave(90, 3);

        // Carol had read everything: she still has.
        $carol = $this->send($this->request('GET', '/api/users/5', ['authenticatedAs' => 5]));
        $this->assertSame(0, json_decode((string) $carol->getBody(), true)['data']['attributes']['unreadDialogCount']);

        // Bob hadn't read carol's message: that is still all he has to read.
        $bob = $this->send($this->request('GET', '/api/dialogs/90', ['authenticatedAs' => 4]));
        $this->assertSame(1, json_decode((string) $bob->getBody(), true)['data']['attributes']['unreadCount']);

        $this->assertEquals(0, $this->emailsTo('carol@machine.local'));
        $this->assertEquals(0, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function the_last_member_out_takes_the_group_with_them(): void
    {
        $this->leave(90, 3);
        $this->leave(90, 4);
        $this->assertTrue(Dialog::query()->whereKey(90)->exists());

        $this->assertEquals(204, $this->leave(90, 5)->getStatusCode());

        $this->assertFalse(Dialog::query()->whereKey(90)->exists());
        $this->assertFalse(DialogMessage::query()->where('dialog_id', 90)->exists());
    }

    #[Test]
    public function a_direct_conversation_cannot_be_left(): void
    {
        $this->assertEquals(403, $this->leave(92, 3)->getStatusCode());
        $this->assertEquals(2, UserDialogState::query()->where('dialog_id', 92)->count());
    }

    #[Test]
    public function someone_not_in_the_group_cannot_leave_it_or_learn_it_exists(): void
    {
        $this->assertEquals(404, $this->leave(90, 6)->getStatusCode());
        $this->assertEquals(404, $this->leave(999, 6)->getStatusCode());
    }

    #[Test]
    public function a_guest_cannot_leave_anything(): void
    {
        $this->assertContains($this->leave(90, null)->getStatusCode(), [400, 401]);
        $this->assertEquals(3, UserDialogState::query()->where('dialog_id', 90)->count());
    }
}
