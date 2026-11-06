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

        // Nothing narrows the list, so it covers every discussion — which the
        // timestamp on the user says in a single write, rather than a row per
        // discussion.
        if (empty($filters)) {
            $actor->markAllAsRead();
            $actor->save();

            return;
        }

        // Only what is still unread needs a row written: the rest is already
        // read, either by a row of its own or by sitting behind the actor's
        // mark-all-as-read timestamp.
        $filters['unread'] = '1';

        $now = Carbon::now();
        $offset = 0;
        $records = [];

        // A broad search or a busy tag can match far more discussions than
        // fit in memory as fully hydrated models at once, so the match set
        // is read a page at a time rather than in a single unbounded
        // `->get()`. Every page is read — and the small `$records` tuples
        // it leaves behind kept — before anything is written: writing
        // `last_read_post_number` as a page is read would make it stop
        // matching `unread`, which would shift what a later page's `offset`
        // lands on and skip discussions the match set still owes a row to.
        while (true) {
            // No sort, and not the default one either: a searcher is free to
            // rewrite the query in order to order it — flarum/sticky unions a
            // second query to float unread stickies — and none of that
            // ordering means anything to the rows being written. Paging by
            // offset is safe regardless, because `AbstractSearcher` always
            // ends the query with a tie breaker on the primary key, making
            // the order of the whole result set stable across the repeated
            // queries below.
            $results = $this->search->query(
                Discussion::class,
                new SearchCriteria($actor, $filters, limit: static::CHUNK_SIZE, offset: $offset, sort: null, sortIsDefault: false)
            );

            /** @var Discussion $discussion */
            foreach ($results->getResults() as $discussion) {
                // A discussion with no posts has no post number to be read up
                // to, and a null `last_read_post_number` would leave it as
                // unread as writing nothing does.
                if ($discussion->last_post_number === null) {
                    continue;
                }

                $records[] = [
                    'user_id' => $actor->id,
                    'discussion_id' => $discussion->id,
                    'last_read_post_number' => $discussion->last_post_number,
                    'last_read_at' => $now,
                ];
            }

            if (! $results->areMoreResults()) {
                break;
            }

            $offset += static::CHUNK_SIZE;
        }

        foreach (array_chunk($records, static::CHUNK_SIZE) as $chunk) {
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
            UserState::query()->upsert(
                $chunk,
                ['user_id', 'discussion_id'],
                ['last_read_post_number', 'last_read_at']
            );
        }
    }

    /**
     * The filters that single out a subset of the discussions, so that a list
     * showing all of them is recognised as such however it was asked for.
     *
     * @param array<string, string|array> $filters
     * @return array<string, string|array>
     */
    protected function narrowingFilters(array $filters): array
    {
        return array_filter(
            $filters,
            fn ($value) => $value !== null && $value !== '' && $value !== []
        );
    }
}
