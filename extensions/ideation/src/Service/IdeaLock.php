<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Service;

use Flarum\Discussion\Discussion;
use Illuminate\Support\Collection;

/**
 * The one lock behind every write to an idea's votes or status: a vote, a
 * move by a moderator or by GitHub, and a merge all lock the discussions'
 * rows here and read the status again under the lock before they write.
 *
 * Lock order, so two writers never wait on each other in opposite orders:
 * the rows are locked in one statement, in ascending id order, before any
 * vote row is written, and a transaction never comes back for more
 * discussion rows than it locked first (a merge locks the ideas already
 * folded into its source with the source and the target). GitHub's link
 * rows are written without holding a discussion's lock, and a member's
 * deletion lowers the counts of what they voted for in one statement.
 *
 * Call it inside a transaction; outside one the lock ends with the
 * statement. SQLite has no row locks and serializes every write instead.
 */
class IdeaLock
{
    /**
     * @return Collection<int, Discussion> keyed by id, holding only the id,
     *                                     status and merge target, read under
     *                                     the lock
     */
    public function lock(int ...$ids): Collection
    {
        return Discussion::query()
            ->whereKey($ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get(['id', 'ideation_status', 'ideation_merged_into_id'])
            ->keyBy('id');
    }

    public function status(int $id): ?string
    {
        return $this->lock($id)->get($id)?->ideation_status;
    }
}
