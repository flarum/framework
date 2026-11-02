<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Ideation\Vote;
use Flarum\User\Event\Deleted;
use Flarum\User\Event\Deleting;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use WeakMap;

/**
 * Take a deleted member's votes off the counts.
 *
 * The foreign key removes the vote rows with the user, so which ideas they
 * voted for is read while the user is being deleted, and the counts are only
 * lowered once the delete has happened: a delete that fails changes nothing.
 */
class ForgetDeletedUserVotes
{
    /** @var WeakMap<User, int[]> */
    protected WeakMap $votedFor;

    public function __construct()
    {
        $this->votedFor = new WeakMap();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(Deleting::class, $this->remember(...));
        $events->listen(Deleted::class, $this->forget(...));
    }

    public function remember(Deleting $event): void
    {
        $this->votedFor[$event->user] = Vote::query()->where('user_id', $event->user->id)->pluck('discussion_id')->map(intval(...))->all();
    }

    public function forget(Deleted $event): void
    {
        $ids = $this->votedFor[$event->user] ?? [];
        unset($this->votedFor[$event->user]);

        if ($ids) {
            Discussion::query()->whereIn('id', $ids)->where('ideation_votes', '>', 0)->decrement('ideation_votes');
        }
    }
}
