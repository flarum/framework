<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Push;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\User\Guest;
use Flarum\User\User;
use Illuminate\Support\Str;
use Pusher\Pusher;
use Throwable;

/**
 * Who is connected, and what they can see. Used to tell members that
 * something they could see has gone, without telling anyone else it existed.
 */
class Audience
{
    public function __construct(
        protected Pusher $pusher
    ) {
    }

    /**
     * Members with a realtime connection open, or null if the websocket server
     * can't be asked (callers then fail closed).
     *
     * @return int[]|null
     */
    public function connectedUserIds(): ?array
    {
        try {
            $response = $this->pusher->getChannels(['filter_by_prefix' => 'private-user=']);
        } catch (Throwable) {
            return null;
        }

        $ids = [];

        foreach ((array) ($response->channels ?? []) as $name => $channel) {
            $ids[] = (int) Str::after((string) $name, 'private-user=');
        }

        return array_values(array_unique($ids));
    }

    public function canSeeDiscussion(int $discussionId, ?User $user = null): bool
    {
        return Discussion::query()->whereKey($discussionId)->whereVisibleTo($user ?? new Guest())->exists();
    }

    public function canSeePost(int $postId, ?User $user = null): bool
    {
        return Post::query()->whereKey($postId)->whereVisibleTo($user ?? new Guest())->exists();
    }

    /**
     * Who can see a discussion right now, captured before it is hidden or
     * deleted. A discussion guests can see is visible to everyone, so that
     * case needs no per-member checks.
     *
     * @return array{public: bool, users: int[]|null}|null
     */
    public function snapshot(Discussion $discussion): ?array
    {
        if ($this->canSeeDiscussion($discussion->id)) {
            return ['public' => true, 'users' => null];
        }

        $connected = $this->connectedUserIds();

        if ($connected === null) {
            return null;
        }

        $users = User::query()->whereIn('id', $connected)->get()
            ->filter(fn (User $user) => $this->canSeeDiscussion($discussion->id, $user))
            ->map(fn (User $user) => $user->id)
            ->values()
            ->all();

        return ['public' => false, 'users' => $users];
    }
}
