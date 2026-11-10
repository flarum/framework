<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\unit\Websocket;

use Flarum\Realtime\Websocket\Channel\Channel;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\TypingActivity;
use Flarum\Realtime\Websocket\TypingIdentity;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ratchet\ConnectionInterface;

/**
 * The forum-wide typing feed is delivered per subscriber: each is told only what
 * they could see anyway. Access checks are stubbed here; their queries are the
 * same visibility scopes used everywhere else.
 */
class TypingActivityTest extends TestCase
{
    private const TYPIST = 7;

    /** @var array<string, object> socketId => connection */
    private array $connections = [];

    /** @var array<int, int> socketId => userId */
    private array $users = [];

    private function subscriber(string $socketId, ?int $userId): object
    {
        $connection = new class implements ConnectionInterface {
            public ?string $socketId = null;
            /** @var array<int, array<string, mixed>> */
            public array $received = [];

            public function send($data): void
            {
                $this->received[] = json_decode($data, true)['data'];
            }

            public function close(): void
            {
            }
        };

        $connection->socketId = $socketId;
        $this->connections[$socketId] = $connection;

        if ($userId !== null) {
            $this->users[$socketId] = $userId;
        }

        return $connection;
    }

    /**
     * @param int[] $canSee         Subscriber ids who can see discussion 42.
     * @param int[] $seesHidden     Subscriber ids with `user.viewLastSeenAt`.
     * @param array<int, int[]> $tags Subscriber id => the tag ids they can see.
     */
    private function activity(bool $subscribed, bool $discloseOnline, array $canSee = [], array $seesHidden = [], array $tags = []): TypingActivity
    {
        $channel = $this->createStub(Channel::class);
        $channel->method('connections')->willReturnCallback(fn () => $this->connections);

        $manager = $this->createStub(Manager::class);
        $manager->method('find')->willReturn($subscribed ? $channel : null);
        $manager->method('userIdForConnection')->willReturnCallback(fn ($c) => $this->users[$c->socketId] ?? null);

        $identities = $this->createStub(TypingIdentity::class);
        $identities->method('for')->willReturn(['displayName' => 'Typist', 'discloseOnline' => $discloseOnline]);

        return new class($manager, $identities, $canSee, $seesHidden, $tags) extends TypingActivity {
            public function __construct(Manager $manager, TypingIdentity $identities, private array $canSee, private array $seesHidden, private array $tags)
            {
                parent::__construct($manager, $identities);
            }

            protected function canSeeDiscussion(int $subscriberId, int $discussionId): bool
            {
                return in_array($subscriberId, $this->canSee, true);
            }

            protected function seesHiddenTypers(int $subscriberId): bool
            {
                return in_array($subscriberId, $this->seesHidden, true);
            }

            protected function visibleTags(int $subscriberId, array $tagIds): array
            {
                return array_values(array_intersect($tagIds, $this->tags[$subscriberId] ?? []));
            }
        };
    }

    #[Test]
    public function nothing_is_sent_while_nobody_is_subscribed(): void
    {
        $admin = $this->subscriber('a', 1);

        $this->activity(subscribed: false, discloseOnline: true, canSee: [1])->discussion(self::TYPIST, 42, null);

        $this->assertSame([], $admin->received);
    }

    #[Test]
    public function only_subscribers_who_can_see_the_discussion_hear_about_it(): void
    {
        $admin = $this->subscriber('a', 1);
        $mod = $this->subscriber('b', 2);

        $this->activity(subscribed: true, discloseOnline: true, canSee: [1])->discussion(self::TYPIST, 42, 123);

        $this->assertSame(
            [['discussionId' => 42, 'tagIds' => null, 'time' => 123, 'userId' => self::TYPIST, 'displayName' => 'Typist']],
            $admin->received
        );
        $this->assertSame([], $mod->received);
    }

    #[Test]
    public function a_hidden_typist_is_named_only_to_subscribers_who_may_see_through_it(): void
    {
        $admin = $this->subscriber('a', 1);
        $mod = $this->subscriber('b', 2);

        $this->activity(subscribed: true, discloseOnline: false, canSee: [1, 2], seesHidden: [1])->discussion(self::TYPIST, 42, null);

        $this->assertSame('Typist', $admin->received[0]['displayName']);
        $this->assertSame(self::TYPIST, $admin->received[0]['userId']);
        $this->assertNull($mod->received[0]['displayName']);
        $this->assertNull($mod->received[0]['userId']);
    }

    #[Test]
    public function the_typist_and_unidentified_connections_are_skipped(): void
    {
        $self = $this->subscriber('a', self::TYPIST);
        $unknown = $this->subscriber('b', null);

        $this->activity(subscribed: true, discloseOnline: true, canSee: [self::TYPIST])->discussion(self::TYPIST, 42, null);

        $this->assertSame([], $self->received);
        $this->assertSame([], $unknown->received);
    }

    #[Test]
    public function an_unidentified_typist_is_not_relayed(): void
    {
        $admin = $this->subscriber('a', 1);

        $this->activity(subscribed: true, discloseOnline: true, canSee: [1])->discussion(null, 42, null);

        $this->assertSame([], $admin->received);
    }

    #[Test]
    public function repeated_pings_from_one_typist_are_throttled(): void
    {
        $admin = $this->subscriber('a', 1);
        $activity = $this->activity(subscribed: true, discloseOnline: true, canSee: [1]);

        $activity->discussion(self::TYPIST, 42, null);
        $activity->discussion(self::TYPIST, 42, null);

        $this->assertCount(1, $admin->received);
    }

    #[Test]
    public function new_discussion_tags_are_narrowed_to_what_each_subscriber_can_see(): void
    {
        $admin = $this->subscriber('a', 1);
        $mod = $this->subscriber('b', 2);

        $this->activity(subscribed: true, discloseOnline: true, tags: [1 => [3, 5], 2 => [3]])->newDiscussion(self::TYPIST, [3, '5', 'x']);

        $this->assertSame([3, 5], $admin->received[0]['tagIds']);
        $this->assertSame([3], $mod->received[0]['tagIds']);
        $this->assertNull($admin->received[0]['discussionId']);
    }
}
