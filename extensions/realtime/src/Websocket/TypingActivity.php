<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket;

use Flarum\Discussion\Discussion;
use Flarum\Realtime\Websocket\Channel\Channel;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\User\User;
use stdClass;

/**
 * A forum-wide feed of who is typing where, for holders of
 * `flarum-realtime.view-all-typing` (e.g. Deck's typing column).
 *
 * Holding the permission gets you onto the channel; it doesn't get you
 * everything said on it. Each subscriber is sent only what they could find out
 * anyway, so this is delivered per connection rather than broadcast:
 *
 *   - typing in a discussion they can't see is never sent to them;
 *   - a typist hiding their online status is named only to subscribers with
 *     `user.viewLastSeenAt` (core's override, as for the typing indicator);
 *   - a new discussion's tags are trimmed to the ones they can see.
 *
 * Private-message typing never comes here. This runs inside the long-lived
 * websocket server on every typing ping, so access checks are cached, and the
 * channel only exists while someone is subscribed, so a forum where nobody is
 * watching pays nothing.
 */
class TypingActivity
{
    public const CHANNEL = 'private-typing-activity';
    public const EVENT = 'typing-activity';

    /** How long an access decision is reused; also bounds how stale one can be. */
    protected const CACHE_TTL_MS = 30000;

    /** Typing pings arrive every 2-3s per typist; subscribers need at most one each. */
    protected const THROTTLE_MS = 2000;

    protected const PRUNE_THRESHOLD = 2000;

    /** @var array<string, array{0: bool, 1: float}> "subscriber:discussion" => [visible, cachedAt] */
    protected array $visibility = [];

    /** @var array<int, array{0: ?User, 1: float}> */
    protected array $users = [];

    /** @var array<string, float> "typist:target" => last relayed */
    protected array $lastRelayed = [];

    public function __construct(
        protected Manager $manager,
        protected TypingIdentity $identities
    ) {
    }

    /**
     * Someone is replying in a discussion.
     */
    public function discussion(?int $typistId, int $discussionId, mixed $time): void
    {
        $channel = $this->manager->find(self::CHANNEL);

        if (! $channel || $typistId === null || $this->throttled("$typistId:d$discussionId")) {
            return;
        }

        $this->deliver($channel, $typistId, function (int $subscriberId) use ($discussionId, $time) {
            if (! $this->canSeeDiscussion($subscriberId, $discussionId)) {
                return null;
            }

            return ['discussionId' => $discussionId, 'tagIds' => null, 'time' => $time];
        });
    }

    /**
     * Someone is writing a new discussion in these tags.
     *
     * @param array<mixed> $tagIds As claimed by the client; only ever narrowed.
     */
    public function newDiscussion(int $typistId, array $tagIds): void
    {
        $channel = $this->manager->find(self::CHANNEL);
        $tagIds = array_values(array_unique(array_filter(array_map('intval', $tagIds))));

        if (! $channel || $this->throttled("$typistId:t".implode(',', $tagIds))) {
            return;
        }

        $this->deliver($channel, $typistId, function (int $subscriberId) use ($tagIds) {
            return ['discussionId' => null, 'tagIds' => $this->visibleTags($subscriberId, $tagIds), 'time' => null];
        });
    }

    /**
     * @param callable(int): ?array $build The event data for one subscriber, or null to skip them.
     */
    protected function deliver(Channel $channel, int $typistId, callable $build): void
    {
        $identity = $this->identities->for($typistId);

        foreach ($channel->connections() as $connection) {
            $subscriberId = $this->manager->userIdForConnection($connection);

            // Unidentified connections fail closed; nobody needs to watch themselves.
            if ($subscriberId === null || $subscriberId === $typistId) {
                continue;
            }

            $data = $build($subscriberId);

            if ($data === null) {
                continue;
            }

            $named = $identity !== null && ($identity['discloseOnline'] || $this->seesHiddenTypers($subscriberId));

            $connection->send(json_encode($this->payload($data + [
                'userId' => $named ? $typistId : null,
                'displayName' => $named ? $identity['displayName'] : null,
            ])));
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function payload(array $data): stdClass
    {
        return (object) [
            'event' => self::EVENT,
            'channel' => self::CHANNEL,
            'data' => $data,
        ];
    }

    protected function canSeeDiscussion(int $subscriberId, int $discussionId): bool
    {
        $key = "$subscriberId:$discussionId";
        $now = $this->now();

        if (isset($this->visibility[$key]) && $now - $this->visibility[$key][1] < self::CACHE_TTL_MS) {
            return $this->visibility[$key][0];
        }

        $user = $this->user($subscriberId);
        $visible = $user !== null && Discussion::whereVisibleTo($user)->whereKey($discussionId)->exists();

        $this->prune($this->visibility, $now, fn (array $entry) => $entry[1]);
        $this->visibility[$key] = [$visible, $now];

        return $visible;
    }

    protected function seesHiddenTypers(int $subscriberId): bool
    {
        return (bool) $this->user($subscriberId)?->hasPermission('user.viewLastSeenAt');
    }

    /**
     * @param int[] $tagIds
     * @return int[]
     */
    protected function visibleTags(int $subscriberId, array $tagIds): array
    {
        $user = $this->user($subscriberId);

        if (! $user || ! $tagIds || ! class_exists(\Flarum\Tags\Tag::class)) {
            return [];
        }

        return \Flarum\Tags\Tag::whereVisibleTo($user)
            ->whereIn('id', $tagIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function user(int $id): ?User
    {
        $now = $this->now();

        if (! isset($this->users[$id]) || $now - $this->users[$id][1] >= self::CACHE_TTL_MS) {
            $this->prune($this->users, $now, fn (array $entry) => $entry[1]);
            $this->users[$id] = [User::query()->find($id), $now];
        }

        return $this->users[$id][0];
    }

    protected function throttled(string $key): bool
    {
        $now = $this->now();

        if (isset($this->lastRelayed[$key]) && $now - $this->lastRelayed[$key] < self::THROTTLE_MS) {
            return true;
        }

        $this->prune($this->lastRelayed, $now, fn (float $at) => $at);
        $this->lastRelayed[$key] = $now;

        return false;
    }

    /**
     * Keeps the long-lived server's caches bounded on a busy forum.
     *
     * @param array<array-key, mixed> $cache
     * @param callable(mixed): float $cachedAt
     */
    protected function prune(array &$cache, float $now, callable $cachedAt): void
    {
        if (count($cache) < self::PRUNE_THRESHOLD) {
            return;
        }

        $cache = array_filter($cache, fn ($entry) => $now - $cachedAt($entry) < self::CACHE_TTL_MS);
    }

    protected function now(): float
    {
        return microtime(true) * 1000;
    }
}
