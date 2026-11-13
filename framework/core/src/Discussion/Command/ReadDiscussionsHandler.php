<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Command;

use Carbon\Carbon;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\UserState;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use RuntimeException;

class ReadDiscussionsHandler
{
    /**
     * How many discussions a single `SearchManager` query fetches, and how
     * many read records are written per upsert statement.
     *
     * One size for both: nothing is gained by writing in smaller batches
     * than are read, since every record read ends up written (bar the few
     * filtered out below).
     */
    public const CHUNK_SIZE = 500;

    public function __construct(
        protected SearchManager $search
    ) {
    }

    public function handle(ReadDiscussions $command): void
    {
        $actor = $command->actor;

        $actor->assertRegistered();

        $filters = $this->narrowingFilters($command->filters);

        // Nothing narrows the list, so it covers every discussion the actor
        // can see. A lone `unread` is the same: it singles out every unread
        // discussion, which is precisely the set the unfiltered list would
        // mark read anyway. Either way it is the whole forum, recorded as the
        // one timestamp on the user rather than a row per discussion.
        if (empty($filters) || array_keys($filters) === ['unread']) {
            $actor->markAllAsRead();
            $actor->save();

            return;
        }

        // Only what is still unread needs a row written: the rest is already
        // read, either by a row of its own or by sitting behind the actor's
        // mark-all-as-read timestamp.
        $filters['unread'] = '1';

        $now = Carbon::now();

        // How many matches at the front of the result set have been looked at
        // and left unread. A discussion with no last post has nothing to mark,
        // so it is skipped — but it keeps matching `unread`. Writing a row, on
        // the other hand, takes a discussion out of the `unread` set, so each
        // page can be written the moment it is read and the next page fetched
        // back at this same offset rather than one that keeps climbing. The
        // offset only ever steps over the skipped rows that would otherwise
        // come back on every query and spin the loop forever.
        //
        // The upshot is that one page is held in memory at a time, and every
        // page's writes are durable as soon as they are made: a request that
        // runs out of time resumes from where it stopped instead of starting
        // the whole match set over.
        $skipped = 0;
        $previousPage = null;

        while (true) {
            // No sort, and not the default one either: a searcher is free to
            // rewrite the query in order to order it — flarum/sticky unions a
            // second query to float unread stickies — and none of that
            // ordering means anything to the rows being written. Paging is
            // stable with the database searcher: `AbstractSearcher` ends the
            // query with a tie breaker on the primary key, so the result set
            // is ordered by id and the skipped rows stay at its front across
            // the repeated queries below.
            $results = $this->search->query(
                Discussion::class,
                new SearchCriteria($actor, $filters, limit: static::CHUNK_SIZE, offset: $skipped, sort: null, sortIsDefault: false)
            );

            /** @var \Illuminate\Database\Eloquent\Collection<int, Discussion> $discussions */
            $discussions = $results->getResults();
            $page = $discussions->mapWithKeys(fn (Discussion $discussion) => [$discussion->id => $discussion->last_post_number])->all();
            ksort($page);

            // A custom search driver may not honour the unread filter. Do not
            // keep writing an unchanged page when the result set cannot shrink.
            if ($page === $previousPage) {
                throw new RuntimeException('The discussion search did not advance after marking results read.');
            }

            $previousPage = $page;
            $records = [];

            /** @var Discussion $discussion */
            foreach ($discussions as $discussion) {
                // A discussion with no posts has no post number to be read up
                // to, and a null `last_read_post_number` would leave it as
                // unread as writing nothing does. Stepping the offset past it
                // keeps the next page from handing back the same unwritten row.
                if ($discussion->last_post_number === null) {
                    $skipped++;

                    continue;
                }

                $records[] = [
                    'user_id' => $actor->id,
                    'discussion_id' => $discussion->id,
                    'last_read_post_number' => $discussion->last_post_number,
                    'last_read_at' => $now,
                ];
            }

            if (! empty($records)) {
                // A discussion the actor has already read part of has a row
                // here, and extensions keep their own columns on it (a
                // subscription, say), so only the read columns are written.
                //
                // This upsert is the only write the bulk path makes: unlike
                // `ReadDiscussionHandler`, it does not raise `UserRead` or
                // dispatch the per-discussion read-state events that go with
                // it, so an extension reacting to a single discussion being
                // read (to send a notification, say) will not see one for a
                // discussion marked read through this endpoint. That trade-off
                // is deliberate — raising events for what may be thousands of
                // discussions at once would undo the point of writing in bulk.
                $query = UserState::query();
                $connection = $query->getModel()->getConnection();
                $grammar = $connection->getQueryGrammar();
                $column = $grammar->wrap('last_read_post_number');
                $current = $grammar->wrap('discussion_user.last_read_post_number');
                $readAt = $grammar->wrap('discussion_user.last_read_at');
                $mysql = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true);
                $incoming = $mysql
                    ? ($connection->getConfig('use_upsert_alias') ? 'laravel_upsert_alias.'.$column : 'values('.$column.')')
                    : 'excluded.'.$column;
                $incomingReadAt = $mysql
                    ? ($connection->getConfig('use_upsert_alias') ? 'laravel_upsert_alias.last_read_at' : 'values(last_read_at)')
                    : 'excluded.last_read_at';
                $advances = "coalesce($current, 0) < $incoming";

                // Compare at write time: another request may have read further
                // since the search. MySQL evaluates assignments left to right,
                // so update the timestamp before changing the compared number.
                $query->upsert($records, ['user_id', 'discussion_id'], [
                    'last_read_at' => $connection->raw("case when $advances then $incomingReadAt else $readAt end"),
                    'last_read_post_number' => $connection->raw("case when $advances then $incoming else $current end"),
                ]);
            }

            if (! $results->areMoreResults()) {
                break;
            }
        }
    }

    /**
     * @param array<string, string|array> $filters
     * @return array<string, string|array>
     */
    protected function narrowingFilters(array $filters): array
    {
        // Only the fulltext filter ignores an empty value; extension filters
        // may narrow the list by their presence alone.
        if (isset($filters['q']) && $filters['q'] === '') {
            unset($filters['q']);
        }

        return $filters;
    }
}
