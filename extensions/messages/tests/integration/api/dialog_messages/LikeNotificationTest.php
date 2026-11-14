<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Extend;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Access\AbstractPolicy;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * With flarum/likes enabled, the author of a message is alerted when someone
 * likes it. Alerts only: a like is never worth an email.
 */
class LikeNotificationTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-messages', 'flarum-likes');

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 202, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subHour()],
                ['id' => 202, 'type' => 'group', 'first_message_id' => 203, 'last_message_id' => 204, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>Hi Alice</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subMinutes(30)],
                ['id' => 202, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Hi Bob</p></t>', 'number' => 2, 'created_at' => Carbon::now()->subMinutes(20)],
                ['id' => 203, 'dialog_id' => 202, 'user_id' => 5, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subMinutes(20)],
                ['id' => 204, 'dialog_id' => 202, 'user_id' => 6, 'content' => '<t><p>Bye all</p></t>', 'number' => 2, 'created_at' => Carbon::now()->subMinutes(10)],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 6, 'joined_at' => Carbon::now()->subHour(), 'left_at' => Carbon::now()->subMinutes(5)],
            ],
        ]);

        $this->setting('flarum-messages.allow_delete_own_messages', '-1');
    }

    protected function like(int $messageId, int $as, string $method = 'POST'): void
    {
        $response = $this->send($this->request($method, "/api/dialog-messages/$messageId/like", ['authenticatedAs' => $as]));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    protected function notificationRows(?int $messageId = null): array
    {
        return $this->database()->table('notifications')
            ->where('type', 'messageLiked')
            ->when($messageId, fn ($query) => $query->where('subject_id', $messageId))
            ->get()->all();
    }

    protected function listNotifications(int $as): array
    {
        $response = $this->send($this->request('GET', '/api/notifications', ['authenticatedAs' => $as]));
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));

        return $body;
    }

    #[Test]
    public function the_author_is_alerted_when_their_message_is_liked(): void
    {
        $this->like(201, 3);

        $rows = $this->notificationRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(4, $rows[0]->user_id);
        $this->assertEquals(3, $rows[0]->from_user_id);
        $this->assertEquals(201, $rows[0]->subject_id);
        $this->assertEquals(0, $rows[0]->is_deleted);
    }

    #[Test]
    public function the_alert_is_listed_with_its_message_and_conversation(): void
    {
        $this->like(201, 3);

        $body = $this->listNotifications(4);

        $this->assertCount(1, $body['data'], json_encode($body));
        $notification = $body['data'][0];
        $this->assertEquals('messageLiked', $notification['attributes']['contentType']);
        $this->assertEquals(['type' => 'dialog-messages', 'id' => '201'], $notification['relationships']['subject']['data']);
        $this->assertEquals(['type' => 'users', 'id' => '3'], $notification['relationships']['fromUser']['data']);

        $message = collect($body['included'])->first(fn ($item) => $item['type'] === 'dialog-messages' && $item['id'] === '201');
        $this->assertNotNull($message, json_encode($body['included']));
        $this->assertEquals(['type' => 'dialogs', 'id' => '201'], $message['relationships']['dialog']['data'] ?? null, json_encode($message));
    }

    /**
     * Six conversations between alice and bob, each with a message by bob that
     * alice likes. One liker, so this counts what the messages cost.
     */
    protected function sixLikedMessages(string $content, array $mentions = []): void
    {
        $dialogs = $messages = $members = $pivots = [];
        foreach (range(0, 5) as $i) {
            $dialogs[] = ['id' => 300 + $i, 'type' => 'direct', 'first_message_id' => 300 + $i, 'last_message_id' => 300 + $i, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subHour()];
            $messages[] = ['id' => 300 + $i, 'dialog_id' => 300 + $i, 'user_id' => 4, 'content' => $content, 'number' => 1, 'created_at' => Carbon::now()->subMinutes(30)];
            $members[] = ['dialog_id' => 300 + $i, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour()];
            $members[] = ['dialog_id' => 300 + $i, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()];
            foreach ($mentions as $table => $column) {
                foreach ((array) $column as $key => $id) {
                    $pivots[$table][] = ['dialog_message_id' => 300 + $i, $key => $id];
                }
            }
        }
        $this->prepareDatabase([Dialog::class => $dialogs, DialogMessage::class => $messages, 'dialog_user' => $members] + $pivots);
    }

    /** The queries listing bob's alerts runs with one liked message, then with six. */
    protected function queriesListingOneThenSix(): array
    {
        $count = 0;
        $this->database()->listen(function () use (&$count) {
            $count++;
        });
        $listing = function () use (&$count): int {
            $before = $count;
            $this->listNotifications(4);

            return $count - $before;
        };

        $this->like(300, 3);
        $one = $listing();

        foreach (range(1, 5) as $i) {
            $this->like(300 + $i, 3);
        }
        $this->assertCount(6, $this->listNotifications(4)['data']);

        return [$one, $listing()];
    }

    #[Test]
    public function listing_alerts_costs_the_same_however_many_there_are(): void
    {
        $this->sixLikedMessages('<t><p>Hello</p></t>');

        [$one, $six] = $this->queriesListingOneThenSix();

        $this->assertLessThanOrEqual($one, $six, "Listing one alert ran $one queries, six ran $six.");
    }

    #[Test]
    public function listing_alerts_about_messages_with_mentions_costs_the_same_however_many_there_are(): void
    {
        $this->extension('flarum-messages', 'flarum-likes', 'flarum-mentions');
        $this->prepareDatabase([
            Discussion::class => [
                ['id' => 50, 'title' => 'Open discussion', 'created_at' => Carbon::now(), 'user_id' => 5, 'first_post_id' => 50, 'comment_count' => 1],
                ['id' => 51, 'title' => 'Hidden discussion', 'created_at' => Carbon::now(), 'user_id' => 5, 'first_post_id' => 51, 'comment_count' => 1, 'is_private' => 1],
            ],
            Post::class => [
                ['id' => 50, 'number' => 1, 'discussion_id' => 50, 'created_at' => Carbon::now(), 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Open</p></t>'],
                ['id' => 51, 'number' => 1, 'discussion_id' => 51, 'created_at' => Carbon::now(), 'user_id' => 5, 'type' => 'comment', 'content' => '<t><p>Hidden</p></t>'],
            ],
        ]);
        $this->sixLikedMessages(
            '<r><p><USERMENTION displayname="carol" id="5">@carol</USERMENTION> <POSTMENTION discussionid="50" displayname="carol" id="50" number="1">@carol#50</POSTMENTION> <POSTMENTION discussionid="51" displayname="carol" id="51" number="1">@carol#51</POSTMENTION></p></r>',
            ['dialog_message_mentions_user' => ['mentions_user_id' => 5], 'dialog_message_mentions_post' => ['mentions_post_id' => 50]]
        );
        $this->database()->table('dialog_message_mentions_post')->insert(array_map(fn ($i) => ['dialog_message_id' => 300 + $i, 'mentions_post_id' => 51], range(0, 5)));

        [$one, $six] = $this->queriesListingOneThenSix();

        $this->assertLessThanOrEqual($one, $six, "Listing one alert ran $one queries, six ran $six.");

        $html = collect($this->listNotifications(4)['included'])->where('type', 'dialog-messages')->pluck('attributes.contentHtml')->implode('');
        $this->assertStringContainsString('class="PostMention" data-id="50"', $html, 'A post bob may see is linked.');
        $this->assertStringContainsString('class="PostMention PostMention--deleted" data-id="51"', $html, 'A post bob may not see is not.');
    }

    protected function showNotificationWithConversation(int $as): array
    {
        $id = $this->database()->table('notifications')->where('type', 'messageLiked')->where('user_id', $as)->value('id');
        $response = $this->send(
            $this->request('GET', "/api/notifications/$id", ['authenticatedAs' => $as])
                ->withQueryParams(['include' => 'subject.dialog.users'])
        );
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));

        return $body;
    }

    #[Test]
    public function an_author_who_left_cannot_read_the_conversation_through_the_alert(): void
    {
        $this->like(203, 3);
        $this->database()->table('dialog_user')->where('dialog_id', 202)->where('user_id', 5)->update(['left_at' => Carbon::now()]);

        $body = $this->showNotificationWithConversation(5);

        $this->assertEmpty(array_filter($body['included'] ?? [], fn ($item) => $item['type'] === 'dialogs'), json_encode($body));
    }

    #[Test]
    public function the_alert_is_on_by_default_and_there_is_no_email_to_turn_on(): void
    {
        $response = $this->send($this->request('GET', '/api/users/4', ['authenticatedAs' => 4]));
        $body = json_decode((string) $response->getBody(), true);

        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));
        $this->assertTrue($body['data']['attributes']['preferences']['notify_messageLiked_alert']);
        $this->assertArrayNotHasKey('notify_messageLiked_email', $body['data']['attributes']['preferences']);
    }

    #[Test]
    public function liking_your_own_message_alerts_nobody(): void
    {
        $this->setting('flarum-likes.like_own_post', '1');

        $this->like(202, 3);

        $this->assertCount(0, $this->notificationRows());
    }

    #[Test]
    public function an_author_who_left_the_conversation_is_not_alerted(): void
    {
        $this->like(204, 3);

        $this->assertCount(0, $this->notificationRows());
    }

    #[Test]
    public function an_author_the_liker_may_not_write_to_is_not_alerted(): void
    {
        $this->extend((new Extend\Policy())->modelPolicy(User::class, NobodyMayWriteToCarol::class));

        $this->like(203, 3);

        $this->assertCount(0, $this->notificationRows());
    }

    #[Test]
    public function the_author_of_a_group_message_is_alerted(): void
    {
        $this->like(203, 3);

        $rows = $this->notificationRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(5, $rows[0]->user_id);
    }

    #[Test]
    public function taking_the_like_back_withdraws_the_alert_and_liking_again_restores_it(): void
    {
        $this->like(201, 3);
        $this->like(201, 3, 'DELETE');

        $rows = $this->notificationRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(1, $rows[0]->is_deleted);
        $this->assertCount(0, $this->listNotifications(4)['data']);

        $this->like(201, 3);

        $rows = $this->notificationRows();
        $this->assertCount(1, $rows);
        $this->assertEquals(0, $rows[0]->is_deleted);
    }

    #[Test]
    public function the_alert_is_no_longer_listed_once_the_author_leaves_the_conversation(): void
    {
        $this->like(203, 3);
        $this->assertCount(1, $this->listNotifications(5)['data']);

        $this->database()->table('dialog_user')->where('dialog_id', 202)->where('user_id', 5)->update(['left_at' => Carbon::now()]);

        $this->assertCount(0, $this->listNotifications(5)['data']);
    }

    #[Test]
    public function deleting_the_message_deletes_its_alerts(): void
    {
        $this->like(201, 3);
        $this->like(202, 4);

        $response = $this->send($this->request('DELETE', '/api/dialog-messages/201', ['authenticatedAs' => 4]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertCount(0, $this->notificationRows(201));
        $this->assertCount(1, $this->notificationRows(202));
    }

    #[Test]
    public function a_conversation_everyone_has_left_takes_its_alerts_with_it(): void
    {
        $this->like(203, 3);
        $this->like(201, 3);

        foreach ([5, 3] as $member) {
            $response = $this->send($this->request('POST', '/api/dialogs/202/leave', ['authenticatedAs' => $member]));
            $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        }

        $this->assertNull($this->database()->table('dialogs')->find(202));
        $this->assertCount(0, $this->notificationRows(203));
        $this->assertCount(1, $this->notificationRows(201));
    }
}

class NobodyMayWriteToCarol extends AbstractPolicy
{
    public function message(User $actor, User $user): ?string
    {
        return $user->id === 5 ? $this->deny() : null;
    }
}
