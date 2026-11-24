<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;

/**
 * Covers both model deletion and bulk deletion that skips model events.
 * Listeners can maintain related records within the deletion transaction.
 *
 * The posts are not loaded: deleting a discussion with a hundred thousand
 * posts costs no more memory than deleting one with four.
 * {@see self::posts()} is the subquery selecting the ids of the rows being
 * deleted, which listeners can use in SQL to reach their own rows without
 * walking a list of ids.
 *
 * The rows are still present while the listeners run, and the discussion rows
 * they belong to are locked for the whole deletion. A write that could change
 * the posts or their likes takes the same discussion lock first, so reading
 * their related rows here is stable; the post rows themselves are not locked
 * separately.
 *
 * A post may be an orphan whose discussion row is already gone when the
 * listeners run: do not look its discussion up with `findOrFail()`, which
 * would throw inside the deletion transaction.
 */
final class DeletingPosts
{
    /** @var list<Closure(): void> */
    private array $callbacks = [];

    /**
     * @param Builder $posts Selects `posts.id` for the rows being deleted.
     * @param list<int> $userIds
     */
    public function __construct(
        public readonly ConnectionInterface $connection,
        private readonly Builder $posts,
        private array $userIds = [],
    ) {
    }

    /**
     * The posts being deleted, as a subquery that selects their `posts.id`.
     *
     * The rows are still present while the event is dispatched; the deletion
     * itself runs afterwards, from the same query. Pass it to
     * `whereIn` to act on related rows in a single statement, for instance
     * `whereIn('post_likes.post_id', $event->posts())`. Callers that need the
     * full rows can `->select('posts.*')` first.
     */
    public function posts(): Builder
    {
        return clone $this->posts;
    }

    /** @param list<int> $ids */
    public function lockUsers(array $ids): void
    {
        $this->userIds = array_merge($this->userIds, $ids);
    }

    /** @internal */
    public function acquireUserLocks(): void
    {
        $ids = array_unique($this->userIds);
        sort($ids);

        foreach (array_chunk($ids, 1000) as $chunk) {
            $this->connection->table('users')->whereIn('id', $chunk)->orderBy('id')->lockForUpdate()->get(['id']);
        }
    }

    /**
     * Register work to run in the deletion transaction once the posts are gone.
     *
     * The callbacks are skipped when the deletion callback reports false, and
     * anything they do rolls back with the transaction if one of them throws.
     *
     * @param Closure(): void $callback
     */
    public function afterDeletion(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /** @internal */
    public function complete(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback();
        }
    }
}
