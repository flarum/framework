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
use Flarum\Messages\Tests\integration\CapturesSentMail;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

/**
 * Where the forum allows it, members can give a group a name, which everyone
 * in it then sees instead of the list of members. The group is told who named
 * it. Off unless an admin turns it on.
 */
class RenameTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use CapturesSentMail;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                // Unconfirmed, so only the Guest group's permissions: she can't send messages.
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 0],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
            ],
            Dialog::class => [
                ['id' => 90, 'type' => 'group', 'first_message_id' => 90, 'last_message_id' => 90, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
                ['id' => 92, 'type' => 'direct', 'first_message_id' => 92, 'last_message_id' => 92, 'last_message_at' => Carbon::now()->subHour(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 90, 'dialog_id' => 90, 'user_id' => 4, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
                ['id' => 92, 'dialog_id' => 92, 'user_id' => 4, 'content' => '<t><p>Hi alice</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subHour()],
            ],
            'dialog_user' => [
                ['dialog_id' => 90, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 90],
                ['dialog_id' => 90, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 90],
                ['dialog_id' => 90, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 90],
                ['dialog_id' => 92, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 92],
                ['dialog_id' => 92, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour(), 'last_read_message_id' => 92],
            ],
        ]);
    }

    protected function allowNames(): void
    {
        $this->setting('flarum-messages.allow_group_names', '1');
    }

    protected function rename(int $dialogId, ?string $name, int $actor = 3): ResponseInterface
    {
        return $this->send($this->request('PATCH', "/api/dialogs/$dialogId", [
            'authenticatedAs' => $actor,
            'json' => ['data' => ['type' => 'dialogs', 'id' => (string) $dialogId, 'attributes' => ['name' => $name]]],
        ]));
    }

    protected function dialog(int $dialogId, int $actor): array
    {
        $response = $this->send($this->request('GET', "/api/dialogs/$dialogId", ['authenticatedAs' => $actor]));

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    /** @return array<int, array> The conversation's messages, oldest first, as $actor sees them. */
    protected function stream(int $dialogId, int $actor): array
    {
        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => $actor])->withQueryParams(['filter' => ['dialog' => (string) $dialogId]])
        );

        return collect(json_decode((string) $response->getBody(), true)['data'])->sortBy('attributes.number')->values()->all();
    }

    #[Test]
    public function groups_cannot_be_named_unless_the_forum_allows_it(): void
    {
        $this->assertEquals(403, $this->rename(90, 'Ski trip')->getStatusCode());
        $this->assertFalse($this->dialog(90, 3)['canRename']);
        $this->assertNull(Dialog::query()->findOrFail(90)->name);
    }

    #[Test]
    public function a_member_names_the_group_and_everyone_in_it_sees_the_name(): void
    {
        $this->allowNames();

        $this->assertTrue($this->dialog(90, 3)['canRename']);

        $response = $this->rename(90, '  Ski trip ');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertEquals('Ski trip', $this->dialog(90, 3)['title']);
        $this->assertEquals('Ski trip', $this->dialog(90, 4)['title']);
        $this->assertEquals('Ski trip', $this->dialog(90, 4)['name']);
    }

    #[Test]
    public function the_group_is_told_who_named_it_and_what_but_not_emailed(): void
    {
        $this->allowNames();
        $this->captureSentMail();

        $this->rename(90, 'Ski trip');

        $notice = collect($this->stream(90, 4))->last();
        $this->assertEquals('renamed', $notice['attributes']['type']);
        $this->assertEquals(['name' => 'Ski trip'], $notice['attributes']['eventData']);
        $this->assertEquals('3', $notice['relationships']['user']['data']['id']);

        // Bob had read everything, and still has.
        $this->assertSame(0, $this->dialog(90, 4)['unreadCount']);
        $this->assertEquals(0, $this->emailsTo('bob@machine.local'));
    }

    #[Test]
    public function taking_the_name_away_brings_back_the_members_as_its_title(): void
    {
        $this->allowNames();

        $this->rename(90, 'Ski trip');
        $this->rename(90, '', actor: 4);

        $this->assertEquals('alice, carol', $this->dialog(90, 4)['title']);
        $this->assertNull($this->dialog(90, 4)['name']);
        $this->assertEquals(['name' => null], collect($this->stream(90, 4))->last()['attributes']['eventData']);
    }

    #[Test]
    public function giving_it_the_name_it_already_has_says_nothing(): void
    {
        $this->allowNames();

        $this->rename(90, 'Ski trip');
        $this->rename(90, 'Ski trip', actor: 4);

        $this->assertCount(1, collect($this->stream(90, 3))->where('attributes.type', 'renamed'));
    }

    #[Test]
    public function a_direct_conversation_cannot_be_named(): void
    {
        $this->allowNames();

        $this->assertEquals(403, $this->rename(92, 'Us')->getStatusCode());
        $this->assertFalse($this->dialog(92, 3)['canRename']);
    }

    #[Test]
    public function a_member_who_cannot_write_in_the_group_cannot_name_it(): void
    {
        $this->allowNames();

        $this->assertEquals(403, $this->rename(90, 'Ski trip', actor: 5)->getStatusCode());
    }

    #[Test]
    public function someone_not_in_the_group_cannot_name_it_or_learn_it_exists(): void
    {
        $this->allowNames();

        $this->assertEquals(404, $this->rename(90, 'Ski trip', actor: 6)->getStatusCode());
    }

    #[Test]
    public function a_name_is_at_most_fifty_characters(): void
    {
        $this->allowNames();

        $this->assertEquals(422, $this->rename(90, str_repeat('a', 51))->getStatusCode());
        $this->assertEquals(200, $this->rename(90, str_repeat('a', 50))->getStatusCode());
    }

    #[Test]
    public function names_are_hidden_again_when_the_forum_stops_allowing_them(): void
    {
        $this->prepareDatabase([Dialog::class => [['id' => 93, 'type' => 'group', 'name' => 'Ski trip', 'created_at' => Carbon::now()]], 'dialog_user' => [
            ['dialog_id' => 93, 'user_id' => 3, 'joined_at' => Carbon::now()],
            ['dialog_id' => 93, 'user_id' => 4, 'joined_at' => Carbon::now()],
            ['dialog_id' => 93, 'user_id' => 6, 'joined_at' => Carbon::now()],
        ]]);

        $this->assertEquals('bob, dave', $this->dialog(93, 3)['title']);
        $this->assertNull($this->dialog(93, 3)['name']);
    }

    #[Test]
    public function naming_a_group_straight_after_writing_is_throttled_as_a_message_is(): void
    {
        $this->allowNames();
        $this->prepareDatabase([DialogMessage::class => [
            ['id' => 94, 'dialog_id' => 90, 'user_id' => 3, 'content' => '<t><p>Hello</p></t>', 'number' => 2, 'created_at' => Carbon::now()],
        ]]);

        $this->assertEquals(429, $this->rename(90, 'Ski trip')->getStatusCode());
    }
}
