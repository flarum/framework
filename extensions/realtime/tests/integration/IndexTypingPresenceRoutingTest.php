<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Tests\integration;

use Carbon\Carbon;
use Flarum\Group\Group;
use Flarum\Realtime\Websocket\Channel\Channel;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Realtime\Websocket\IndexTypingPresence;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

/**
 * The index-typing presence tracker surfaces, in each broadcast, the tag IDs the
 * receiving channel's audience may light up on the tag list. The IDs are scoped
 * per channel so a restricted tag is never disclosed to an audience that can't
 * see it: the public channel carries a guest-visible discussion's tags, while
 * each restricted-tag channel carries only its own tag.
 */
class IndexTypingPresenceRoutingTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-realtime');

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(), // id 2
            ],
            Group::class => [
                ['id' => 100, 'name_singular' => 'Member', 'name_plural' => 'Members'],
            ],
            'group_user' => [
                ['user_id' => 2, 'group_id' => 100],
            ],
            'group_permission' => [
                // Group 100 (user 2) can view restricted tag 3, but NOT tag 4.
                ['group_id' => 100, 'permission' => 'tag3.viewForum'],
            ],
            'tags' => [
                ['id' => 1, 'name' => 'Open', 'slug' => 'open', 'is_restricted' => false],
                ['id' => 2, 'name' => 'Secondary', 'slug' => 'secondary', 'is_restricted' => false],
                ['id' => 3, 'name' => 'Members', 'slug' => 'members', 'is_restricted' => true],
                ['id' => 4, 'name' => 'Staff', 'slug' => 'staff', 'is_restricted' => true],
            ],
            'discussions' => [
                // Guest-visible: tagged with two unrestricted tags.
                ['id' => 1, 'title' => 'Public', 'slug' => 'public', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
                // Restricted: tagged with two restricted tags.
                ['id' => 2, 'title' => 'Restricted', 'slug' => 'restricted', 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 1, 'first_post_id' => 0, 'comment_count' => 1, 'is_private' => 0],
            ],
            'discussion_tag' => [
                ['discussion_id' => 1, 'tag_id' => 1],
                ['discussion_id' => 1, 'tag_id' => 2],
                ['discussion_id' => 2, 'tag_id' => 3],
                ['discussion_id' => 2, 'tag_id' => 4],
            ],
        ]);
    }

    /**
     * Build a presence tracker whose Manager hands out a spy channel (always
     * "has connections") for every channel name, recording each broadcast.
     *
     * The broadcasts are collected into a shared ArrayObject so the caller sees
     * what the channel spies append (a plain array returned by value would be a
     * snapshot taken before touch() runs).
     *
     * @return array{0: IndexTypingPresence, 1: \ArrayObject<int, object>}
     */
    private function presenceCapturingBroadcasts(): array
    {
        $broadcasts = new \ArrayObject();

        $channelFor = function () use ($broadcasts): Channel {
            // A stub (not a mock) — we only need it to record broadcasts, not to
            // assert call expectations.
            $channel = $this->createStub(Channel::class);

            $channel->method('hasConnections')->willReturn(true);
            $channel->method('broadcast')->willReturnCallback(function (object $payload) use ($broadcasts) {
                $broadcasts->append($payload);

                return true;
            });

            return $channel;
        };

        $manager = $this->createStub(Manager::class);
        $manager->method('find')->willReturnCallback($channelFor);

        // Boot the app so the global container (used by IndexTypingPresence's
        // resolve() / Eloquent model connections) is bound to the test database.
        $this->app();

        return [new IndexTypingPresence($manager), $broadcasts];
    }

    /**
     * Like {@link presenceCapturingBroadcasts} but the presence's clock is driven by
     * a `&$clock` reference (ms), so tests can advance time deterministically to
     * exercise the rising-edge refresh / falling-edge expiry without real sleeps.
     *
     * @return array{0: IndexTypingPresence, 1: \ArrayObject<int, object>, 2: object}
     */
    private function presenceWithControllableClock(): array
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        $clock = new class() {
            public float $now = 1_000_000.0;
        };

        $controllable = new class($presence, $clock) extends IndexTypingPresence {
            public function __construct(private IndexTypingPresence $inner, private object $clock)
            {
                // Reuse the already-built presence's Manager.
                $reflection = new \ReflectionProperty(IndexTypingPresence::class, 'manager');
                parent::__construct($reflection->getValue($inner));
            }

            protected function now(): float
            {
                return $this->clock->now;
            }
        };

        return [$controllable, $broadcasts, $clock];
    }

    #[Test]
    public function continued_typing_re_broadcasts_the_rising_edge_to_refresh_the_dot(): void
    {
        [$presence, $broadcasts, $clock] = $this->presenceWithControllableClock();

        $presence->touch(1);            // rising edge
        $clock->now += 1000;            // 1s later — still within REFRESH window
        $presence->touch(1);            // coalesced, no broadcast
        $clock->now += 3500;            // now > REFRESH_MS since last broadcast
        $presence->touch(1);            // refresh re-broadcast

        // Two `typing:true` broadcasts: the initial rising edge and the refresh.
        $this->assertCount(2, $broadcasts);
        $this->assertTrue($broadcasts[0]->data['typing']);
        $this->assertTrue($broadcasts[1]->data['typing']);
    }

    #[Test]
    public function rapid_typing_within_the_refresh_window_stays_coalesced(): void
    {
        [$presence, $broadcasts, $clock] = $this->presenceWithControllableClock();

        $presence->touch(1);
        $clock->now += 500;
        $presence->touch(1);
        $clock->now += 500;
        $presence->touch(1);

        // All within REFRESH_MS of the first broadcast → a single rising edge.
        $this->assertCount(1, $broadcasts);
    }

    #[Test]
    public function public_discussion_routes_to_the_public_channel_with_all_its_tags(): void
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        $presence->touch(1);

        $this->assertCount(1, $broadcasts);
        $this->assertSame(IndexTypingPresence::PUBLIC_CHANNEL, $broadcasts[0]->channel);
        $this->assertSame(1, $broadcasts[0]->data['id']);
        $this->assertTrue($broadcasts[0]->data['typing']);
        // Both of the public discussion's (guest-visible) tags are surfaced.
        $this->assertEqualsCanonicalizing([1, 2], $broadcasts[0]->data['tags']);
    }

    #[Test]
    public function restricted_discussion_routes_per_tag_with_only_that_tag_disclosed(): void
    {
        $this->setting('flarum-realtime.index-typing-indicator-restricted', '1');

        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        $presence->touch(2);

        // One broadcast per restricted tag channel.
        $this->assertCount(2, $broadcasts);

        $byChannel = [];
        foreach ($broadcasts as $payload) {
            $byChannel[$payload->channel] = $payload->data['tags'];
        }

        // Each restricted-tag channel carries ONLY its own tag — never the
        // discussion's other restricted tag, which its audience may not see.
        $this->assertSame([3], $byChannel[IndexTypingPresence::tagChannel(3)] ?? null);
        $this->assertSame([4], $byChannel[IndexTypingPresence::tagChannel(4)] ?? null);
    }

    #[Test]
    public function restricted_discussion_is_not_surfaced_when_the_setting_is_off(): void
    {
        // Setting defaults off; no restricted routing, so nothing is broadcast.
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        $presence->touch(2);

        $this->assertCount(0, $broadcasts);
    }

    #[Test]
    public function compose_typing_routes_public_tags_to_the_public_channel(): void
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        // User 2 composing a new discussion in the public tag 1.
        $presence->touchTags(2, [1]);

        $this->assertCount(1, $broadcasts);
        $this->assertSame(IndexTypingPresence::PUBLIC_CHANNEL, $broadcasts[0]->channel);
        $this->assertSame([1], $broadcasts[0]->data['tags']);
        $this->assertTrue($broadcasts[0]->data['typing']);
        // No discussion id yet, but a per-user source key for dedup.
        $this->assertArrayNotHasKey('id', $broadcasts[0]->data);
        $this->assertSame('u2', $broadcasts[0]->data['source']);
    }

    #[Test]
    public function compose_typing_routes_a_visible_restricted_tag_to_its_channel(): void
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        // Tag 3 is restricted but user 2 can view it.
        $presence->touchTags(2, [3]);

        $this->assertCount(1, $broadcasts);
        $this->assertSame(IndexTypingPresence::tagChannel(3), $broadcasts[0]->channel);
        $this->assertSame([3], $broadcasts[0]->data['tags']);
    }

    #[Test]
    public function compose_typing_drops_a_restricted_tag_the_actor_cannot_see(): void
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        // Tag 4 is restricted and user 2 CANNOT see it: claiming it must be ignored,
        // never broadcast — otherwise a client could disclose activity in a tag it
        // has no access to.
        $presence->touchTags(2, [4]);

        $this->assertCount(0, $broadcasts);
    }

    #[Test]
    public function compose_typing_drops_hidden_tags_but_keeps_visible_ones(): void
    {
        [$presence, $broadcasts] = $this->presenceCapturingBroadcasts();

        // Mixed claim: visible public 1, visible restricted 3, hidden restricted 4.
        $presence->touchTags(2, [1, 3, 4]);

        $channels = [];
        foreach ($broadcasts as $payload) {
            $channels[] = $payload->channel;
        }

        // Tag 4 is dropped; only the two the actor can see are surfaced.
        $this->assertCount(2, $broadcasts);
        $this->assertContains(IndexTypingPresence::PUBLIC_CHANNEL, $channels);
        $this->assertContains(IndexTypingPresence::tagChannel(3), $channels);
        $this->assertNotContains(IndexTypingPresence::tagChannel(4), $channels);
    }
}
