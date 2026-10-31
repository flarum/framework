<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * A message to more than one recipient starts a group conversation: a new one
 * every time, with the sender and every recipient as members.
 */
class GroupCreateTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        // Not emailed: core queues one job per email, and the sync queue runs
        // each inside the request, which isn't what is under test here.
        $quiet = json_encode(['notify_messageReceived_email' => false]);

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 7, 'username' => 'erin', 'email' => 'erin@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 8, 'username' => 'frank', 'email' => 'frank@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
                ['id' => 9, 'username' => 'grace', 'email' => 'grace@machine.local', 'is_email_confirmed' => 1, 'preferences' => $quiet],
            ],
            // These tests send several messages in a row; throttling has its own.
            'group_permission' => [
                ['permission' => 'dialog.sendMessageWithoutThrottle', 'group_id' => 3],
            ],
        ]);
    }

    protected function sendTo(array $recipients, int $actor = 3): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/dialog-messages', [
                'authenticatedAs' => $actor,
                'json' => [
                    'data' => [
                        'type' => 'dialog-messages',
                        'attributes' => [
                            'content' => 'Hello, everyone!',
                            'users' => array_map(fn (int $id) => ['id' => $id], $recipients),
                        ],
                    ],
                ],
            ])->withQueryParams(['include' => 'dialog.users'])
        );
    }

    #[Test]
    public function a_message_to_two_recipients_starts_a_group_conversation_with_all_three(): void
    {
        $response = $this->sendTo([4, 5]);

        $body = (string) $response->getBody();
        $this->assertEquals(201, $response->getStatusCode(), $body);

        $data = json_decode($body, true);
        $dialog = collect($data['included'])->firstWhere('type', 'dialogs');

        $this->assertEquals('group', $dialog['attributes']['type']);
        $this->assertEqualsCanonicalizing(['3', '4', '5'], collect($dialog['relationships']['users']['data'])->pluck('id')->all());
    }

    #[Test]
    public function the_new_group_comes_back_with_every_member(): void
    {
        $response = $this->sendTo([4, 5, 6, 7, 8, 9]);

        $this->assertEquals(201, $response->getStatusCode());

        $dialog = collect(json_decode((string) $response->getBody(), true)['included'])->firstWhere('type', 'dialogs');
        $this->assertCount(7, $dialog['relationships']['users']['data']);
    }

    #[Test]
    public function the_same_recipients_get_a_new_group_each_time(): void
    {
        $first = $this->dialogIdOf($this->sendTo([4, 5]));
        $second = $this->dialogIdOf($this->sendTo([5, 4], actor: 3));

        $this->assertNotEquals($first, $second);
        $this->assertEquals(2, Dialog::query()->where('type', 'group')->count());
    }

    #[Test]
    public function a_single_recipient_still_continues_the_direct_conversation_the_pair_already_have(): void
    {
        $this->prepareDatabase([
            Dialog::class => [
                ['id' => 50, 'type' => 'direct', 'first_message_id' => 50, 'last_message_id' => 50, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
            ],
            DialogMessage::class => [
                ['id' => 50, 'dialog_id' => 50, 'user_id' => 4, 'content' => '<t><p>Hi</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'dialog_user' => [
                ['dialog_id' => 50, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay()],
                ['dialog_id' => 50, 'user_id' => 4, 'joined_at' => Carbon::now()->subDay()],
            ],
        ]);

        // A group the pair share doesn't count as their direct conversation.
        $this->sendTo([4, 5]);

        $this->assertEquals('50', $this->dialogIdOf($this->sendTo([4], actor: 3)));
    }

    #[Test]
    public function the_sender_listed_among_the_recipients_is_not_counted_twice(): void
    {
        $response = $this->sendTo([3, 4]);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('direct', Dialog::query()->findOrFail($this->dialogIdOf($response))->type);
    }

    protected function dialogIdOf(ResponseInterface $response): string
    {
        $body = (string) $response->getBody();

        $this->assertEquals(201, $response->getStatusCode(), $body);

        return json_decode($body, true)['data']['relationships']['dialog']['data']['id'];
    }
}
