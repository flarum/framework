<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialogs;

use Carbon\Carbon;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * What a member sees of a group: all of its members, and a title naming the
 * others.
 */
class GroupDisplayTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $users = [];
        $members = [];

        // alice (3) and six others.
        foreach (['alice', 'bob', 'carol', 'dave', 'erin', 'frank', 'grace'] as $i => $name) {
            $users[] = ['id' => $i + 3, 'username' => $name, 'email' => "$name@machine.local", 'is_email_confirmed' => 1];
            $members[] = ['dialog_id' => 90, 'user_id' => $i + 3, 'joined_at' => Carbon::now()->subDay()];
        }

        $this->prepareDatabase([
            User::class => $users,
            Dialog::class => [
                ['id' => 90, 'type' => 'group', 'first_message_id' => 90, 'last_message_id' => 90, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
                ['id' => 91, 'type' => 'group', 'first_message_id' => 91, 'last_message_id' => 91, 'last_message_at' => Carbon::now()->subDay(), 'created_at' => Carbon::now()->subDay()],
            ],
            DialogMessage::class => [
                ['id' => 90, 'dialog_id' => 90, 'user_id' => 3, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
                ['id' => 91, 'dialog_id' => 91, 'user_id' => 3, 'content' => '<t><p>Hi you two</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subDay()],
            ],
            'dialog_user' => [
                ...$members,
                ['dialog_id' => 91, 'user_id' => 3, 'joined_at' => Carbon::now()->subDay()],
                ['dialog_id' => 91, 'user_id' => 5, 'joined_at' => Carbon::now()->subDay()],
                ['dialog_id' => 91, 'user_id' => 4, 'joined_at' => Carbon::now()->subDay()],
            ],
        ]);
    }

    protected function dialogs(int $actor): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialogs', ['authenticatedAs' => $actor])->withQueryParams(['include' => 'users'])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        return collect(json_decode((string) $response->getBody(), true)['data'])->keyBy('id')->all();
    }

    #[Test]
    public function every_member_of_a_group_is_listed(): void
    {
        $members = collect($this->dialogs(3)['90']['relationships']['users']['data'])->pluck('id')->all();

        $this->assertEqualsCanonicalizing(['3', '4', '5', '6', '7', '8', '9'], $members);
    }

    #[Test]
    public function every_member_of_a_group_is_listed_when_it_comes_with_its_messages(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])
                ->withQueryParams(['filter' => ['dialog' => '90'], 'include' => 'dialog.users'])
        );

        $dialog = collect(json_decode((string) $response->getBody(), true)['included'])->first(fn ($item) => $item['type'] === 'dialogs');

        $this->assertCount(7, $dialog['relationships']['users']['data']);
    }

    #[Test]
    public function messages_from_many_conversations_load_their_titles_together(): void
    {
        $dialogs = $messages = $members = [];

        // Six more groups of alice, bob and carol.
        foreach (range(100, 105) as $id) {
            $dialogs[] = ['id' => $id, 'type' => 'group', 'first_message_id' => $id, 'last_message_id' => $id, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()];
            $messages[] = ['id' => $id, 'dialog_id' => $id, 'user_id' => 3, 'content' => "<t><p>Group $id</p></t>", 'number' => 1, 'created_at' => Carbon::now()];

            foreach ([3, 4, 5] as $userId) {
                $members[] = ['dialog_id' => $id, 'user_id' => $userId, 'joined_at' => Carbon::now()];
            }
        }

        $this->prepareDatabase([Dialog::class => $dialogs, DialogMessage::class => $messages, 'dialog_user' => $members]);

        // As the message search asks: each message with its conversation.
        $response = $this->send($this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3]));

        $this->assertEquals(200, $response->getStatusCode());
        $titles = collect(json_decode((string) $response->getBody(), true)['included'])->where('type', 'dialogs')->pluck('attributes.title');
        $this->assertContains('bob, carol', $titles->all());
    }

    #[Test]
    public function the_dialog_endpoint_lists_every_member_of_a_group(): void
    {
        $response = $this->send($this->request('GET', '/api/dialogs/90', ['authenticatedAs' => 3])->withQueryParams(['include' => 'users']));

        $this->assertCount(7, json_decode((string) $response->getBody(), true)['data']['relationships']['users']['data']);
    }

    #[Test]
    public function a_group_is_titled_with_up_to_three_other_members_in_alphabetical_order_and_how_many_more(): void
    {
        $this->assertEquals('bob, carol, dave +3', $this->dialogs(3)['90']['attributes']['title']);
        // Never the viewer themselves.
        $this->assertEquals('alice, bob, carol +3', $this->dialogs(9)['90']['attributes']['title']);
    }

    #[Test]
    public function a_small_group_is_titled_with_all_its_other_members(): void
    {
        $this->assertEquals('bob, carol', $this->dialogs(3)['91']['attributes']['title']);
    }
}
