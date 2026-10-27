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
     * How many read records are written per statement.
     */
    public const CHUNK_SIZE = 100;

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
        // mark-all-as-read timestamp. Besides being less to write, this keeps
        // what has to be loaded bounded by the work there is to do.
        $filters['unread'] = '1';

        // No sort, and not the default one either: a searcher is free to
        // rewrite the query in order to order it — flarum/sticky unions a
        // second query to float unread stickies — and none of that ordering
        // means anything to the rows being written.
        $results = $this->search->query(
            Discussion::class,
            new SearchCriteria($actor, $filters, limit: null, sort: null, sortIsDefault: false)
        );

        $now = Carbon::now();
        $records = [];

        /** @var Discussion $discussion */
        foreach ($results->getResults() as $discussion) {
            // A discussion with no posts has no post number to be read up to,
            // and a null `last_read_post_number` would leave it as unread as
            // writing nothing does.
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

        foreach (array_chunk($records, static::CHUNK_SIZE) as $chunk) {
            // A discussion the actor has already read part of has a row here,
            // and extensions keep their own columns on it (a subscription,
            // say), so only the read columns are written.
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
