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

/**
 * Covers both model deletion and bulk deletion that skips model events.
 * Listeners can maintain related records within the deletion transaction.
 */
final class DeletingPosts
{
    /** @var list<Closure(): void> */
    private array $callbacks = [];

    /**
     * @param list<int> $postIds
     * @param list<int> $userIds
     */
    public function __construct(
        public readonly ConnectionInterface $connection,
        public readonly array $postIds,
        private array $userIds = [],
    ) {
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

    /** @param Closure(): void $callback */
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
