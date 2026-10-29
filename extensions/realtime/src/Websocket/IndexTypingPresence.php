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
     * While typing continues, re-broadcast the (still-true) rising edge at least this
     * often. The frontend dot self-clears EXPIRY_MS after the last signal it received,
     * but we coalesce a typing burst into a single rising edge — so without a periodic
     * refresh the dot would vanish mid-typing once EXPIRY_MS elapsed. Re-announcing
     * comfortably inside that window keeps the dot alive for as long as the typing does.
     */
    protected const REFRESH_MS = 3000;

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
     * Key => timestamp (ms) of the most recent rising-edge broadcast for that key
     * (discussion id, or "{userId}:{tagId}" for compose typing). Used to re-announce
     * a still-active typer before the frontend's dot TTL elapses. See REFRESH_MS.
     */
    protected array $lastBroadcast = [];

    /**
     * discussionId => [array<string, int[]> $channels, float $cachedAt]. Maps each
     * channel a discussion's typing presence is broadcast to → the tag IDs that
     * channel's audience may see lit up on the tag list. The public channel if it's
     * guest-visible (carrying the discussion's guest-visible tags), otherwise its
     * restricted-tag channels (when enabled, each carrying only its own tag), or an
     * empty map if it can't be surfaced.
     */
    protected array $routing = [];

    /**
     * "{userId}:{tagId}" => timestamp (ms) of the most recent compose ping. Tracks
     * users composing a *new* discussion in a tag — there's no discussion id yet, so
     * the client tells us which tags it has selected and we key presence per
     * (user, tag). Kept separate from {@link $lastSeen} (which is discussion-keyed).
     */
    protected array $tagLastSeen = [];

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

        // Rising edge when newly active, or a periodic refresh while typing continues
        // (see REFRESH_MS) so the frontend dot doesn't self-clear mid-typing.
        if (! $wasActive || $this->needsRefresh($discussionId, $now)) {
            $this->lastBroadcast[$discussionId] = $now;
            $this->broadcast($discussionId, true);
        }
    }

    /**
     * Whether a still-active typer's rising edge should be re-broadcast because the
     * last one is old enough that the frontend dot is about to self-clear.
     */
    protected function needsRefresh(int|string $key, float $now): bool
    {
        return ! isset($this->lastBroadcast[$key])
            || ($now - $this->lastBroadcast[$key]) >= self::REFRESH_MS;
    }

    /**
     * Record that a user is composing a *new* discussion in the given tags. Unlike
     * {@link touch}, there's no discussion yet, so the client tells us which tags it
     * has selected and we re-authorise each against the actor before surfacing it —
     * the IDs are client-supplied, so trusting them blindly would let a user light up
     * (and thus disclose activity in) a restricted tag they can't see.
     *
     * Each (user, visible-tag) pair coalesces independently: a rising-edge signal is
     * emitted only when that pair was idle, and the falling edge is emitted by
     * {@link sweep} once the compose ping stops. Tags the actor can't see are dropped.
     *
     * @param int[] $claimedTagIds
     */
    public function touchTags(int $userId, array $claimedTagIds): void
    {
        if (! class_exists(\Flarum\Tags\Tag::class) || empty($claimedTagIds)) {
            return;
        }

        $now = $this->now();

        foreach ($this->visibleTags($userId, $claimedTagIds) as $tagId) {
            $key = "$userId:$tagId";
            $wasActive = $this->isTagActive($key, $now);

            $this->tagLastSeen[$key] = $now;

            // Rising edge, or a periodic refresh while compose typing continues, so
            // the dot doesn't self-clear mid-typing (mirrors touch()).
            if (! $wasActive || $this->needsRefresh($key, $now)) {
                $this->lastBroadcast[$key] = $now;
                $this->broadcastTagTyping($userId, $tagId, true);
            }
        }
    }

    /**
     * Filter client-claimed tag IDs to those the actor may actually see, so a restricted
     * tag is never surfaced to (or on behalf of) someone who can't see it.
     *
     * @param int[] $claimedTagIds
     * @return int[]
     */
    protected function visibleTags(int $userId, array $claimedTagIds): array
    {
        $actor = \Flarum\User\User::query()->find($userId) ?? new Guest;

        return \Flarum\Tags\Tag::whereVisibleTo($actor)
            ->whereIn('id', array_map('intval', $claimedTagIds))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function isTagActive(string $key, float $now): bool
    {
        return isset($this->tagLastSeen[$key])
            && ($now - $this->tagLastSeen[$key]) < self::EXPIRY_MS;
    }

    /**
     * Broadcast a compose-typing signal for a single (already-authorised) tag. Routes
     * to the public channel if the tag is guest-visible, otherwise to its restricted
     * channel — mirroring {@link resolveChannels}. Carries no discussion `id` (there
     * isn't one yet); only the tag-list dot consumes this, keyed on `tags`.
     */
    protected function broadcastTagTyping(int $userId, int $tagId, bool $typing): void
    {
        $channelName = $this->tagIsGuestVisible($tagId)
            ? self::PUBLIC_CHANNEL
            : self::tagChannel($tagId);

        $channel = $this->manager->find($channelName);

        if (! $channel || ! $channel->hasConnections()) {
            return;
        }

        $channel->broadcast((object) [
            'event' => 'index-typing',
            'channel' => $channelName,
            'data' => [
                // No discussion `id` yet — this is a new-discussion compose. `source`
                // is a per-typer dedup key so concurrent composers in the same tag
                // don't clear each other's dot (mirrors how `id` keys reply typing).
                'source' => "u$userId",
                'typing' => $typing,
                'tags' => [$tagId],
            ],
        ]);
    }

    protected function tagIsGuestVisible(int $tagId): bool
    {
        return \Flarum\Tags\Tag::whereVisibleTo(new Guest)->where('id', $tagId)->exists();
    }

    /**
     * The channels a discussion's typing presence should be broadcast to, each
     * mapped to the tag IDs that channel's audience may see lit up on the tag list.
     * Cached briefly so a typing burst doesn't re-query. Mirrors the visibility
     * scoping the rest of the realtime extension uses (see AuthController / Push\Jobs\Job).
     *
     * @return array<string, int[]>
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
     * Resolve the channel → visible-tag-IDs map for a discussion.
     *
     * The tag IDs are surfaced in the broadcast so the tag list can light up the
     * tags a typing discussion belongs to. They're scoped per channel so a restricted
     * tag is never disclosed to an audience that can't see it: the public channel
     * carries the discussion's guest-visible tags, while each restricted-tag channel
     * carries only its own tag (the one its subscribers are authorised for).
     *
     * @return array<string, int[]>
     */
    protected function resolveChannels(int $discussionId): array
    {
        if (Discussion::whereVisibleTo(new Guest)->where('id', $discussionId)->exists()) {
            // A guest-visible discussion's tags are themselves guest-visible, so it's
            // safe to surface all of them on the public channel.
            $tagIds = class_exists(\Flarum\Tags\Tag::class)
                ? $this->tagIdsFor($discussionId, restrictedOnly: false)
                : [];

            return [self::PUBLIC_CHANNEL => $tagIds];
        }

        if (! $this->restrictedEnabled() || ! class_exists(\Flarum\Tags\Tag::class)) {
            return [];
        }

        // The discussion's restricted tags. A user who can see ANY of them can see
        // the discussion (Flarum's OR semantics), so broadcasting to each restricted
        // tag's channel reaches exactly the right audience. Each channel carries only
        // its own tag ID — a subscriber is authorised for that tag but not necessarily
        // for the discussion's other (restricted) tags.
        $channels = [];

        foreach ($this->tagIdsFor($discussionId, restrictedOnly: true) as $tagId) {
            $channels[self::tagChannel($tagId)] = [$tagId];
        }

        return $channels;
    }

    /**
     * The IDs of a discussion's tags, optionally limited to restricted tags. Queried
     * via the pivot to avoid loading models in the hot path.
     *
     * @return int[]
     */
    protected function tagIdsFor(int $discussionId, bool $restrictedOnly): array
    {
        $query = resolve(ConnectionInterface::class)
            ->table('discussion_tag')
            ->join('tags', 'tags.id', '=', 'discussion_tag.tag_id')
            ->where('discussion_tag.discussion_id', $discussionId);

        if ($restrictedOnly) {
            $query->where('tags.is_restricted', true);
        }

        return $query->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
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
                unset($this->lastSeen[$discussionId], $this->lastBroadcast[$discussionId]);
                $this->broadcast($discussionId, false);
            }
        }

        // Falling edge for new-discussion compose typing, keyed per (user, tag).
        foreach ($this->tagLastSeen as $key => $lastSeen) {
            if (! $this->isTagActive($key, $now)) {
                unset($this->tagLastSeen[$key], $this->lastBroadcast[$key]);
                [$userId, $tagId] = array_map('intval', explode(':', $key));
                $this->broadcastTagTyping($userId, $tagId, false);
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
        foreach ($this->channelsFor($discussionId) as $channelName => $tagIds) {
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
                    // The tags this channel's audience may see lit up on the tag list.
                    'tags' => $tagIds,
                ],
            ]);
        }
    }

    protected function now(): float
    {
        return microtime(true) * 1000;
    }
}
