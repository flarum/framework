<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Listener;

use Flarum\Discussion\Discussion;
use Flarum\Ideation\Service\IdeaLock;
use Flarum\Ideation\Vote;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;
use WeakMap;

/**
 * Take a deleted member's votes off the counts.
 *
 * The foreign key removes the vote rows with the user, so which ideas they
 * voted for is read while the user is being deleted, and the counts are only
 * lowered once the delete has happened: a delete that fails changes nothing.
 *
 * Eloquent's own model events rather than core's User\Event\Deleting and
 * Deleted: those come only from the API's delete, and flarum/gdpr erases a
 * member with a plain `$user->delete()`. Every delete of a User model fires
 * these, once each.
 *
 * Both steps lock the ideas through IdeaLock (one statement, ascending id)
 * before any vote row or count changes, the order every vote, move and
 * merge uses. A delete run inside a transaction (an erasure that removes
 * several things at once) therefore holds the ideas' locks before the
 * foreign key removes their vote rows, and never waits on a merge or a vote
 * in the opposite order. Outside a transaction each statement stands alone.
 */
class ForgetDeletedUserVotes
{
    /** @var WeakMap<User, int[]> */
    protected WeakMap $votedFor;

    public function __construct(
        protected IdeaLock $lock
    ) {
        $this->votedFor = new WeakMap();
    }

    public function subscribe(Dispatcher $events): void
    {
        $events->listen('eloquent.deleting: '.User::class, $this->remember(...));
        $events->listen('eloquent.deleted: '.User::class, $this->forget(...));
    }

    /** Returns nothing: a `false` from a `deleting` listener would cancel the delete. */
    public function remember(User $user): void
    {
        $ids = Vote::query()->where('user_id', $user->id)->pluck('discussion_id')->map(intval(...))->all();
        $this->votedFor[$user] = $ids;

        if ($ids) {
            $this->lock->lock(...$ids);
        }
    }

    public function forget(User $user): void
    {
        $ids = $this->votedFor[$user] ?? [];
        unset($this->votedFor[$user]);

        if ($ids) {
            $user->getConnection()->transaction(function () use ($ids) {
                $this->lock->lock(...$ids);

                Discussion::query()->whereIn('id', $ids)->where('ideation_votes', '>', 0)->decrement('ideation_votes');
            });
        }
    }
}
