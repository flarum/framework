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

        while (true) {
            // No sort, and not the default one either: a searcher is free to
            // rewrite the query in order to order it — flarum/sticky unions a
            // second query to float unread stickies — and none of that
            // ordering means anything to the rows being written. Paging is
            // safe regardless, because `AbstractSearcher` always ends the
            // query with a tie breaker on the primary key, so the result set
            // is ordered by id and the skipped rows stay at its front across
            // the repeated queries below.
            $results = $this->search->query(
                Discussion::class,
                new SearchCriteria($actor, $filters, limit: static::CHUNK_SIZE, offset: $skipped, sort: null, sortIsDefault: false)
            );

            $records = [];

            /** @var Discussion $discussion */
            foreach ($results->getResults() as $discussion) {
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
                UserState::query()->upsert(
                    $records,
                    ['user_id', 'discussion_id'],
                    ['last_read_post_number', 'last_read_at']
                );
            }

            if (! $results->areMoreResults()) {
                break;
            }
        }
    }

    /**
     * Filter keys that narrow the list by their presence alone: their value is
     * ignored by the filter, so even an empty one still constrains the search.
     *
     * @var list<string>
     */
    protected const PRESENCE_FILTERS = ['hidden', '-hidden'];

    /**
     * The filters that single out a subset of the discussions, so that a list
     * showing all of them is recognised as such however it was asked for.
     *
     * An empty value is taken to narrow nothing and dropped — an empty `q`
     * searches for nothing, an empty `author` names no one — so a request
     * carrying only empties is the whole forum. The exception is the handful
     * of filters switched on by their presence alone (`filter[hidden]=` and
     * `filter[-hidden]=` carry an empty value yet still narrow): dropping those
     * would let a direct API client marking a `hidden` list read silence the
     * whole forum instead.
     *
     * @param array<string, string|array> $filters
     * @return array<string, string|array>
     */
    protected function narrowingFilters(array $filters): array
    {
        return array_filter(
            $filters,
            function ($value, $key) {
                if (in_array($key, static::PRESENCE_FILTERS, true)) {
                    return true;
                }

                return $value !== null && $value !== '' && $value !== [];
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
}
