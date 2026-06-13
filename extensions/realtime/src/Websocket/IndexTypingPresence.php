<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket;

use Flarum\Discussion\Discussion;
use Flarum\Realtime\Websocket\Channel\Manager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Guest;
use Illuminate\Database\ConnectionInterface;

/**
 * Tracks which discussions currently have someone typing, and broadcasts a
 * coalesced, presence-only signal to the index channels so the discussion list
 * can show an ambient "someone is typing here" dot.
 *
 * This runs in-process inside the long-lived websocket server (see ServeCommand),
 * so state is a plain in-memory map — no Redis or DB needed. The raw `client-typing`
 * firehose (one event every 2–3s per typer, carrying the typer's name) is never
 * relayed to the list. Instead we emit at most one `index-typing` event per
 * discussion per typing burst:
 *
 *   - rising edge  → { id, typing: true }  when a discussion goes from idle to typing
 *   - falling edge → { id, typing: false } when TTL elapses with no further pings
 *
 * No display name or user id is ever included — the list only needs to know *that*
 * someone is typing, not *who*. Opening the discussion reveals who, via the existing
 * permission-checked TypingIndicator.
 */
class IndexTypingPresence
{
    /**
     * The single public channel guest-visible discussions are surfaced on.
     * Presence-only payload, so a public channel is safe.
     */
    public const PUBLIC_CHANNEL = 'public-index-typing';

    /**
     * Channel name for a restricted tag's typing presence. A discussion that isn't
     * guest-visible is routed to the channels of its restricted tags; a user may
     * only subscribe to one if they can see that tag (enforced in AuthController),
     * so the audience matches Flarum's tag-scoped OR visibility semantics. Bounded
     * by tag count, not user count — keeps the Phase 1 scale guarantee. Only used
     * when the `index-typing-indicator-restricted` setting is on and tags is active.
     */
    public static function tagChannel(int $tagId): string
    {
        return "private-index-typing-tag=$tagId";
    }

    /**
     * A discussion is considered "still being typed in" for this many ms after the
     * last `client-typing` ping. Mirrors the frontend TypingState EXPIRY_MS so the
     * dot and the in-discussion indicator clear on the same cadence.
     */
    protected const EXPIRY_MS = 6000;

    /**
     * The set of channels a discussion's presence routes to is cached this many ms,
     * so a repeated typing burst on the same discussion doesn't re-query the database.
     */
    protected const ROUTING_TTL_MS = 30000;

    /**
     * discussionId => timestamp (ms) of the most recent typing ping.
     */
    protected array $lastSeen = [];

    /**
     * discussionId => [string[] $channels, float $cachedAt]. The channels a
     * discussion's typing presence is broadcast to — the public channel if it's
     * guest-visible, otherwise its restricted-tag channels (when enabled), or an
     * empty array if it can't be surfaced.
     */
    protected array $routing = [];

    protected ?bool $restrictedEnabled = null;

    public function __construct(protected Manager $manager)
    {
    }

    /**
     * Record a typing ping for a discussion. Broadcasts a rising-edge signal only
     * when the discussion was previously idle; repeat pings during an active burst
     * are coalesced (no broadcast).
     *
     * The discussion is routed to the channels its audience can see: the public
     * channel if guest-visible, otherwise its restricted-tag channels (when the
     * restricted setting is on and tags is active). A discussion that can't be
     * surfaced anywhere never emits a signal — surfacing a restricted discussion's
     * ID/activity on a channel its audience can't see would leak it.
     */
    public function touch(int $discussionId): void
    {
        if (empty($this->channelsFor($discussionId))) {
            return;
        }

        $now = $this->now();
        $wasActive = $this->isActive($discussionId, $now);

        $this->lastSeen[$discussionId] = $now;

        if (! $wasActive) {
            $this->broadcast($discussionId, true);
        }
    }

    /**
     * The channels a discussion's typing presence should be broadcast to. Cached
     * briefly so a typing burst doesn't re-query. Mirrors the visibility scoping the
     * rest of the realtime extension uses (see AuthController / Push\Jobs\Job).
     *
     * @return string[]
     */
    protected function channelsFor(int $discussionId): array
    {
        $now = $this->now();

        if (isset($this->routing[$discussionId])
            && ($now - $this->routing[$discussionId][1]) < self::ROUTING_TTL_MS) {
            return $this->routing[$discussionId][0];
        }

        $channels = $this->resolveChannels($discussionId);
        $this->routing[$discussionId] = [$channels, $now];

        return $channels;
    }

    /**
     * @return string[]
     */
    protected function resolveChannels(int $discussionId): array
    {
        if (Discussion::whereVisibleTo(new Guest)->where('id', $discussionId)->exists()) {
            return [self::PUBLIC_CHANNEL];
        }

        if (! $this->restrictedEnabled() || ! class_exists(\Flarum\Tags\Tag::class)) {
            return [];
        }

        // The discussion's restricted tags. A user who can see ANY of them can see
        // the discussion (Flarum's OR semantics), so broadcasting to each restricted
        // tag's channel reaches exactly the right audience. Queried via the pivot to
        // avoid loading models in the hot path.
        $tagIds = resolve(ConnectionInterface::class)
            ->table('discussion_tag')
            ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
            ->where('discussion_tag.discussion_id', $discussionId)
            ->where('tags.is_restricted', true)
            ->pluck('tags.id');

        return $tagIds->map(fn ($id) => self::tagChannel((int) $id))->all();
    }

    protected function restrictedEnabled(): bool
    {
        if ($this->restrictedEnabled === null) {
            $this->restrictedEnabled = (bool) resolve(SettingsRepositoryInterface::class)
                ->get('flarum-realtime.index-typing-indicator-restricted');
        }

        return $this->restrictedEnabled;
    }

    /**
     * Expire discussions whose last ping is older than the TTL, broadcasting a
     * falling-edge signal for each. Call periodically from the server event loop.
     */
    public function sweep(): void
    {
        $now = $this->now();

        foreach ($this->lastSeen as $discussionId => $lastSeen) {
            if (! $this->isActive($discussionId, $now)) {
                unset($this->lastSeen[$discussionId]);
                $this->broadcast($discussionId, false);
            }
        }

        // Evict expired routing entries so the cache doesn't grow unbounded over
        // the lifetime of the long-running server process.
        foreach ($this->routing as $discussionId => [$channels, $cachedAt]) {
            if (($now - $cachedAt) >= self::ROUTING_TTL_MS) {
                unset($this->routing[$discussionId]);
            }
        }
    }

    protected function isActive(int $discussionId, float $now): bool
    {
        return isset($this->lastSeen[$discussionId])
            && ($now - $this->lastSeen[$discussionId]) < self::EXPIRY_MS;
    }

    protected function broadcast(int $discussionId, bool $typing): void
    {
        foreach ($this->channelsFor($discussionId) as $channelName) {
            $channel = $this->manager->find($channelName);

            // Only allocate work if someone is actually listening on the list.
            if (! $channel || ! $channel->hasConnections()) {
                continue;
            }

            $channel->broadcast((object) [
                'event' => 'index-typing',
                'channel' => $channelName,
                'data' => [
                    'id' => $discussionId,
                    'typing' => $typing,
                ],
            ]);
        }
    }

    protected function now(): float
    {
        return microtime(true) * 1000;
    }
}
