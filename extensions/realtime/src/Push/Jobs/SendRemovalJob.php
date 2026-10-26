<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push\Jobs;

use Flarum\Discussion\Discussion;
use Flarum\Realtime\Push\Audience;
use Flarum\User\User;
use Pusher\Pusher;

/**
 * Tells members who could see a post or discussion that it has been hidden or
 * deleted, so their screens can drop it. The event carries only its type and
 * id, and goes only to members who could see it before and can't now: anyone
 * who still can (moderators, for a hidden post) is sent its new state instead.
 */
class SendRemovalJob extends Job
{
    public const EVENT = 'removed';

    /** Pusher accepts up to this many channels per trigger. */
    protected const CHANNELS_PER_TRIGGER = 100;

    /**
     * @param array{public: bool, users: int[]|null}|null $snapshot For discussions: who could see it beforehand.
     */
    public function __construct(
        protected string $type,
        protected int $id,
        protected ?int $discussionId,
        protected ?array $snapshot = null
    ) {
        parent::__construct();
    }

    public static function forPost(int $postId, int $discussionId): self
    {
        return new self('posts', $postId, $discussionId);
    }

    /**
     * @param array{public: bool, users: int[]|null}|null $snapshot
     */
    public static function forDiscussion(int $discussionId, ?array $snapshot): self
    {
        return new self('discussions', $discussionId, $discussionId, $snapshot);
    }

    public function handle(Pusher $pusher, Audience $audience): void
    {
        [$userIds, $public] = $this->type === 'posts'
            ? $this->postAudience($audience)
            : $this->discussionAudience($audience);

        $payload = [
            'data' => ['type' => $this->type, 'id' => (string) $this->id],
            'meta' => ['discussionId' => $this->discussionId !== null ? (string) $this->discussionId : null],
        ];

        $channels = array_map(fn (int $id) => "private-user=$id", $userIds);

        foreach (array_chunk($channels, self::CHANNELS_PER_TRIGGER) as $chunk) {
            $pusher->trigger($chunk, self::EVENT, $payload);
        }

        // Guests only have the public channel. Members ignore removals there:
        // theirs arrive on their own channel, worked out for them.
        if ($public) {
            $pusher->trigger('public', self::EVENT, $payload);
        }
    }

    /**
     * Anyone who can see the discussion could have seen the post in it.
     *
     * @return array{0: int[], 1: bool}
     */
    protected function postAudience(Audience $audience): array
    {
        if ($this->discussionId === null || ! Discussion::query()->whereKey($this->discussionId)->exists()) {
            return [[], false];
        }

        $connected = $audience->connectedUserIds() ?? [];

        $userIds = $this->users($connected)
            ->filter(fn (User $user) => $audience->canSeeDiscussion($this->discussionId, $user))
            ->filter(fn (User $user) => ! $audience->canSeePost($this->id, $user))
            ->map(fn (User $user) => $user->id)
            ->values()
            ->all();

        return [$userIds, $audience->canSeeDiscussion($this->discussionId) && ! $audience->canSeePost($this->id)];
    }

    /**
     * Who could see it was captured before it changed; without that snapshot
     * nobody is told, rather than risk telling people it existed.
     *
     * @return array{0: int[], 1: bool}
     */
    protected function discussionAudience(Audience $audience): array
    {
        if ($this->snapshot === null) {
            return [[], false];
        }

        $candidates = $this->snapshot['public']
            ? ($audience->connectedUserIds() ?? [])
            : ($this->snapshot['users'] ?? []);

        $userIds = $this->users($candidates)
            ->filter(fn (User $user) => ! $audience->canSeeDiscussion($this->id, $user))
            ->map(fn (User $user) => $user->id)
            ->values()
            ->all();

        return [$userIds, $this->snapshot['public'] && ! $audience->canSeeDiscussion($this->id)];
    }

    /**
     * @param int[] $ids
     * @return \Illuminate\Support\Collection<int, User>
     */
    protected function users(array $ids): \Illuminate\Support\Collection
    {
        return $ids ? User::query()->whereIn('id', $ids)->get()->toBase() : collect();
    }
}
