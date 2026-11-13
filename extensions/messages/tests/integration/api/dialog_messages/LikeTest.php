<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Tests\integration\api\dialog_messages;

use Carbon\Carbon;
use Flarum\Extend;
use Flarum\Group\Group;
use Flarum\Messages\Api\DialogMessageLikeThrottler;
use Flarum\Messages\Dialog;
use Flarum\Messages\DialogMessage;
use Flarum\Messages\DialogMessage\Event\Liked;
use Flarum\Messages\DialogMessage\Event\Unliked;
use Flarum\Realtime\Push\Jobs\SendDialogMessageJob;
use Flarum\Realtime\Push\Jobs\SendGeneratedPayloadJob;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use PHPUnit\Framework\Attributes\Test;
use Pusher\Pusher;
use stdClass;

/**
 * With flarum/likes enabled, the members of a conversation can like its
 * messages. Nobody outside it can, admins included, since nobody outside it
 * can see them.
 */
class LikeTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var array<int, Liked|Unliked> */
    public static array $events = [];

    protected function setUp(): void
    {
        parent::setUp();

        self::$events = [];

        $this->extension('flarum-messages', 'flarum-likes');

        $this->extend(
            (new Extend\Event())
                ->listen(Liked::class, fn (Liked $event) => self::$events[] = $event)
                ->listen(Unliked::class, fn (Unliked $event) => self::$events[] = $event)
        );

        $this->prepareDatabase([
            User::class => [
                ['id' => 3, 'username' => 'alice', 'email' => 'alice@machine.local', 'is_email_confirmed' => 1],
                ['id' => 4, 'username' => 'bob', 'email' => 'bob@machine.local', 'is_email_confirmed' => 1],
                ['id' => 5, 'username' => 'carol', 'email' => 'carol@machine.local', 'is_email_confirmed' => 1],
                ['id' => 6, 'username' => 'dave', 'email' => 'dave@machine.local', 'is_email_confirmed' => 1],
                ['id' => 7, 'username' => 'erin', 'email' => 'erin@machine.local', 'is_email_confirmed' => 1],
            ],
            'group_user' => [
                ['user_id' => 7, 'group_id' => Group::MODERATOR_ID],
            ],
            Dialog::class => [
                ['id' => 201, 'type' => 'direct', 'first_message_id' => 201, 'last_message_id' => 203, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subHour()],
                ['id' => 202, 'type' => 'group', 'first_message_id' => 204, 'last_message_id' => 204, 'last_message_at' => Carbon::now(), 'created_at' => Carbon::now()->subHour()],
            ],
            DialogMessage::class => [
                ['id' => 201, 'dialog_id' => 201, 'user_id' => 4, 'content' => '<t><p>Hi Alice</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subMinutes(30)],
                ['id' => 202, 'dialog_id' => 201, 'user_id' => 3, 'content' => '<t><p>Hi Bob</p></t>', 'number' => 2, 'created_at' => Carbon::now()->subMinutes(20)],
                ['id' => 203, 'dialog_id' => 201, 'user_id' => 3, 'type' => 'renamed', 'content' => '{"name":"Chat"}', 'number' => 3, 'created_at' => Carbon::now()->subMinutes(10)],
                ['id' => 204, 'dialog_id' => 202, 'user_id' => 3, 'content' => '<t><p>Hi all</p></t>', 'number' => 1, 'created_at' => Carbon::now()->subMinutes(10)],
            ],
            'dialog_user' => [
                ['dialog_id' => 201, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 201, 'user_id' => 4, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 3, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 5, 'joined_at' => Carbon::now()->subHour()],
                ['dialog_id' => 202, 'user_id' => 6, 'joined_at' => Carbon::now()->subHour(), 'left_at' => Carbon::now()->subMinutes(5)],
            ],
        ]);
    }

    protected function like(int $messageId, ?int $as, string $method = 'POST'): array
    {
        $response = $this->send($this->request($method, "/api/dialog-messages/$messageId/like", $as ? ['authenticatedAs' => $as] : []));

        return [$response->getStatusCode(), json_decode((string) $response->getBody(), true)];
    }

    protected function likers(int $messageId): array
    {
        return $this->database()->table('dialog_message_likes')->where('dialog_message_id', $messageId)->pluck('user_id')->all();
    }

    #[Test]
    public function a_member_can_like_a_message_and_sees_it_in_the_response(): void
    {
        [$status, $body] = $this->like(201, 3);

        $this->assertEquals(200, $status, json_encode($body));
        $this->assertEquals([3], $this->likers(201));
        $this->assertEquals(1, $body['data']['attributes']['likesCount']);
        $this->assertTrue($body['data']['attributes']['canLike']);
        $this->assertEquals([['type' => 'users', 'id' => '3']], $body['data']['relationships']['likes']['data']);
    }

    #[Test]
    public function liking_twice_keeps_one_like(): void
    {
        $this->like(201, 3);
        [$status, $body] = $this->like(201, 3);

        $this->assertEquals(200, $status);
        $this->assertEquals([3], $this->likers(201));
        $this->assertEquals(1, $body['data']['attributes']['likesCount']);
    }

    #[Test]
    public function a_member_can_take_their_like_back(): void
    {
        $this->like(201, 3);
        [$status, $body] = $this->like(201, 3, 'DELETE');

        $this->assertEquals(200, $status, json_encode($body));
        $this->assertEquals([], $this->likers(201));
        $this->assertEquals(0, $body['data']['attributes']['likesCount']);
        $this->assertEquals([], $body['data']['relationships']['likes']['data']);
    }

    /**
     * What realtime tells the other members by: once per change, and not for
     * a like that was already there or a like that wasn't.
     */
    #[Test]
    public function liking_and_unliking_raise_an_event_only_when_something_changed(): void
    {
        $this->like(201, 3);
        $this->like(201, 3);
        $this->like(201, 3, 'DELETE');
        $this->like(201, 3, 'DELETE');

        $this->assertEquals([Liked::class, Unliked::class], array_map(get_class(...), self::$events));
        $this->assertEquals([201, 201], array_map(fn ($event) => $event->message->id, self::$events));
        $this->assertEquals([3, 3], array_map(fn ($event) => $event->user->id, self::$events));
    }

    #[Test]
    public function a_member_of_a_group_conversation_can_like_its_messages(): void
    {
        [$status] = $this->like(204, 5);

        $this->assertEquals(200, $status);
        $this->assertEquals([5], $this->likers(204));
    }

    #[Test]
    public function nobody_outside_the_conversation_can_like_its_messages(): void
    {
        // An admin, a moderator and a member who are not in it.
        foreach ([1, 7, 5] as $userId) {
            [$status] = $this->like(201, $userId);
            $this->assertEquals(404, $status, "user $userId");
        }

        $this->assertEquals([], $this->likers(201));
    }

    #[Test]
    public function a_member_who_left_cannot_like_its_messages(): void
    {
        [$status] = $this->like(204, 6);

        $this->assertEquals(404, $status);
        $this->assertEquals([], $this->likers(204));
    }

    #[Test]
    public function a_guest_cannot_like_a_message_or_take_a_like_back(): void
    {
        // Past the CSRF check, which a guest without a session never gets
        // through, to what the endpoints themselves answer.
        $this->extend(
            (new Extend\Csrf())
                ->exemptRoute('dialog-messages.like')
                ->exemptRoute('dialog-messages.unlike')
        );

        $this->database()->table('dialog_message_likes')->insert(['dialog_message_id' => 201, 'user_id' => 3]);

        // Core looks the message up, as the guest may see it, before asking
        // who they are: as with every request a guest makes for a message,
        // there is none.
        $this->assertEquals(404, $this->send($this->request('GET', '/api/dialog-messages/201'))->getStatusCode());

        [$status, $body] = $this->like(201, null);
        $this->assertEquals(404, $status, json_encode($body));

        [$status, $body] = $this->like(201, null, 'DELETE');
        $this->assertEquals(404, $status, json_encode($body));

        $this->assertEquals([3], $this->likers(201));
    }

    #[Test]
    public function a_notice_cannot_be_liked(): void
    {
        [$status] = $this->like(203, 4);

        $this->assertEquals(403, $status);
        $this->assertEquals([], $this->likers(203));
    }

    #[Test]
    public function own_messages_can_be_liked_where_the_forum_allows_liking_own_posts(): void
    {
        [$status] = $this->like(202, 3);

        $this->assertEquals(200, $status);
        $this->assertEquals([3], $this->likers(202));
    }

    #[Test]
    public function own_messages_cannot_be_liked_where_the_forum_does_not_allow_liking_own_posts(): void
    {
        $this->setting('flarum-likes.like_own_post', false);

        [$status] = $this->like(202, 3);
        $this->assertEquals(403, $status);
        $this->assertEquals([], $this->likers(202));

        // Someone else's message still can be.
        [$status] = $this->like(201, 3);
        $this->assertEquals(200, $status);
    }

    /**
     * Liking messages goes with liking posts: a group the forum doesn't let
     * like posts can't like messages either.
     */
    #[Test]
    public function only_members_allowed_to_like_posts_can_like_messages(): void
    {
        $this->database()->table('group_permission')->where('permission', 'discussion.likePosts')->delete();

        [$status] = $this->like(201, 3);

        $this->assertEquals(403, $status);
        $this->assertEquals([], $this->likers(201));
    }

    #[Test]
    public function a_like_can_be_taken_back_after_the_member_may_no_longer_like(): void
    {
        $this->database()->table('dialog_message_likes')->insert(['dialog_message_id' => 201, 'user_id' => 3]);
        $this->database()->table('group_permission')->where('permission', 'discussion.likePosts')->delete();

        [$status, $body] = $this->like(201, 3, 'DELETE');

        $this->assertEquals(200, $status, json_encode($body));
        $this->assertEquals([], $this->likers(201));
    }

    #[Test]
    public function nobody_outside_the_conversation_can_take_back_a_like_in_it(): void
    {
        $this->database()->table('dialog_message_likes')->insert(['dialog_message_id' => 201, 'user_id' => 3]);

        [$status] = $this->like(201, 5, 'DELETE');

        $this->assertEquals(404, $status);
        $this->assertEquals([3], $this->likers(201));
    }

    /**
     * A like answers a message, so it goes with being allowed to answer: a
     * member who can't reply in a conversation can't like in it either.
     */
    #[Test]
    public function a_member_who_cannot_reply_in_a_conversation_cannot_like_in_it(): void
    {
        $this->database()->table('group_permission')->where('permission', 'dialog.sendMessage')->delete();

        [$status] = $this->like(201, 3);
        $this->assertEquals(403, $status);

        // A group with its replies closed, the same.
        [$status] = $this->like(204, 5);
        $this->assertEquals(403, $status);

        $this->assertEquals([], $this->likers(201));
        $this->assertEquals([], $this->likers(204));
    }

    #[Test]
    public function a_member_can_like_where_the_conversation_is_open_to_replies_from_anyone(): void
    {
        $this->database()->table('group_permission')->where('permission', 'dialog.sendMessage')->delete();
        $this->database()->table('dialogs')->whereIn('id', [201, 202])->update(['anyone_can_reply' => true]);

        $this->assertEquals(200, $this->like(201, 3)[0]);
        $this->assertEquals(200, $this->like(204, 5)[0]);
    }

    #[Test]
    public function the_message_list_tells_a_member_who_cannot_reply_they_cannot_like(): void
    {
        $this->database()->table('group_permission')->where('permission', 'dialog.sendMessage')->delete();

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '201']])
        );

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));
        $this->assertFalse(collect($body['data'])->firstWhere('id', '201')['attributes']['canLike']);
    }

    /**
     * Each like reaches every member online, so a member can't flick one on
     * and off as fast as they can send requests.
     */
    #[Test]
    public function liking_and_unliking_are_rate_limited(): void
    {
        DialogMessageLikeThrottler::$maxAttempts = 3;

        try {
            $this->assertEquals(200, $this->like(201, 3)[0]);
            $this->assertEquals(200, $this->like(201, 3, 'DELETE')[0]);
            $this->assertEquals(200, $this->like(201, 3)[0]);

            [$status] = $this->like(201, 3, 'DELETE');
            $this->assertEquals(429, $status);
            $this->assertEquals([3], $this->likers(201));

            // Counted for each member: someone else in the conversation can still like.
            $this->assertEquals(200, $this->like(202, 4)[0]);

            // And reading messages isn't held up.
            $this->assertEquals(200, $this->send(
                $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '201']])
            )->getStatusCode());
        } finally {
            DialogMessageLikeThrottler::$maxAttempts = 30;
        }
    }

    /**
     * Flicking one like on and off sends the whole conversation an update
     * each time, so a message takes only a few changes a minute from one
     * member, however much else they like.
     */
    #[Test]
    public function one_message_can_be_liked_and_unliked_only_a_few_times_a_minute(): void
    {
        DialogMessageLikeThrottler::$maxPerMessage = 4;

        try {
            foreach (['POST', 'DELETE', 'POST', 'DELETE'] as $method) {
                $this->assertEquals(200, $this->like(201, 3, $method)[0], $method);
            }

            $this->assertEquals(429, $this->like(201, 3)[0]);
            $this->assertEquals([], $this->likers(201));

            // Another message is a fresh count.
            $this->assertEquals(200, $this->like(202, 3)[0]);
        } finally {
            DialogMessageLikeThrottler::$maxPerMessage = 4;
        }
    }

    #[Test]
    public function those_allowed_to_message_without_throttle_are_not_rate_limited_liking(): void
    {
        DialogMessageLikeThrottler::$maxAttempts = 1;

        try {
            // An admin, in the group conversation.
            $this->prepareDatabase(['dialog_user' => [['dialog_id' => 202, 'user_id' => 1, 'joined_at' => Carbon::now()->subHour()]]]);

            $this->assertEquals(200, $this->like(204, 1)[0]);
            $this->assertEquals(200, $this->like(204, 1, 'DELETE')[0]);
            $this->assertEquals(200, $this->like(204, 1)[0]);
        } finally {
            DialogMessageLikeThrottler::$maxAttempts = 30;
        }
    }

    #[Test]
    public function the_message_list_carries_likes_you_first(): void
    {
        $this->database()->table('dialog_message_likes')->insert([
            ['dialog_message_id' => 202, 'user_id' => 4, 'created_at' => Carbon::now()->subMinutes(2)],
            ['dialog_message_id' => 202, 'user_id' => 3, 'created_at' => Carbon::now()->subMinute()],
        ]);

        $response = $this->send(
            $this->request('GET', '/api/dialog-messages', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['dialog' => '201']])
        );

        $body = json_decode((string) $response->getBody(), true);
        $this->assertEquals(200, $response->getStatusCode(), json_encode($body));

        $messages = collect($body['data'])->keyBy('id');

        $this->assertEquals(2, $messages['202']['attributes']['likesCount']);
        $this->assertEquals(['3', '4'], array_column($messages['202']['relationships']['likes']['data'], 'id'));
        $this->assertTrue($messages['201']['attributes']['canLike']);
        $this->assertFalse($messages['203']['attributes']['canLike']);
        $this->assertContains('4', array_column(array_filter($body['included'], fn ($i) => $i['type'] === 'users'), 'id'));
    }

    #[Test]
    public function liking_a_message_does_not_count_as_liking_a_post(): void
    {
        $this->like(201, 3);

        $response = $this->send(
            $this->request('GET', '/api/posts', ['authenticatedAs' => 3])->withQueryParams(['filter' => ['likedBy' => '3']])
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], json_decode((string) $response->getBody(), true)['data']);
    }

    /**
     * With flarum/realtime, the other members see a like as it happens, each
     * as they would see the message, and nobody else gets it.
     */
    #[Test]
    public function realtime_tells_the_connected_members_of_the_conversation(): void
    {
        $this->extension('flarum-messages', 'flarum-likes', 'flarum-realtime');

        $pushed = [];
        $pusher = $this->createMock(Pusher::class);
        // Bob and carol are connected; carol isn't in this conversation.
        $pusher->method('getChannels')->willReturn((object) ['channels' => (object) [
            'private-user=4' => new stdClass(),
            'private-user=5' => new stdClass(),
        ]]);
        $pusher->method('trigger')->willReturnCallback(function ($channel, $event, $data) use (&$pushed) {
            $pushed[] = [$channel, $event, $data];

            return new stdClass();
        });
        $this->app()->getContainer()->instance(Pusher::class, $pusher);

        [$status] = $this->like(201, 3);
        $this->assertEquals(200, $status);

        $this->assertCount(1, $pushed);
        [$channel, $event, $data] = $pushed[0];
        $this->assertEquals('private-user=4', $channel);
        $this->assertEquals(Liked::class, $event);

        $payload = is_string($data) ? json_decode($data, true) : json_decode(json_encode($data), true);
        $this->assertEquals('201', $payload['data']['id']);
        $this->assertEquals(1, $payload['data']['attributes']['likesCount']);
        $this->assertEquals([['type' => 'users', 'id' => '3']], $payload['data']['relationships']['likes']['data']);
    }

    /**
     * Two members liking one message at once: the second like still goes out
     * while the first is being sent, rather than being dropped as a duplicate.
     */
    #[Test]
    public function realtime_sends_a_like_made_while_another_on_the_same_message_is_going_out(): void
    {
        $this->extension('flarum-messages', 'flarum-likes', 'flarum-realtime');

        $pushed = [];
        $pusher = $this->createMock(Pusher::class);
        $pusher->method('getChannels')->willReturn((object) ['channels' => (object) ['private-user=4' => new stdClass()]]);
        $pusher->method('trigger')->willReturnCallback(function ($channel, $event) use (&$pushed) {
            $pushed[] = [$channel, $event];

            return new stdClass();
        });
        $this->app()->getContainer()->instance(Pusher::class, $pusher);

        // Another like of the same message, still being sent to bob.
        $message = DialogMessage::findOrFail(201);
        $cache = $this->app()->getContainer()->make(Cache::class);

        foreach ([new SendDialogMessageJob(Liked::class, $message), new SendGeneratedPayloadJob(Liked::class, $message, User::findOrFail(4))] as $earlier) {
            /** @var WithoutOverlapping $overlap */
            $overlap = $earlier->middleware()[0];
            $this->assertTrue($cache->lock($overlap->getLockKey($earlier), 60)->get());
        }

        [$status] = $this->like(201, 3);
        $this->assertEquals(200, $status);

        $this->assertEquals([['private-user=4', Liked::class]], $pushed);
    }
}
