<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky;

use DateTime;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Tags\Search\Filter\TagFilter;
use Illuminate\Database\Query\Builder;

class PinStickiedDiscussionsToTop
{
    public function __invoke(DatabaseSearchState $state, SearchCriteria $criteria): void
    {
        if ($criteria->sortIsDefault && ! $state->isFulltextSearch()) {
            $query = $state->getQuery()->getQuery();

            // If we are viewing a specific tag, then pin all stickied
            // discussions to the top no matter what.
            $filters = $state->getActiveFilters();

            if ($count = count($filters)) {
                if ($count === 1 && $filters[0] instanceof TagFilter) {
                    if (! is_array($query->orders)) {
                        $query->orders = [];
                    }

                    array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);
                }

                return;
            }

            // Otherwise, if we are viewing "all discussions", only pin stickied
            // discussions to the top if they are unread. To do this in a
            // performant way we create another query which will select all
            // stickied discussions, marry them into the main query, and then
            // reorder the unread ones up to the top.
            $sticky = clone $query;
            $sticky->where('is_sticky', true);
            unset($sticky->orders);

            $epochTime = (new DateTime('@0'))->format('Y-m-d H:i:s');

            /** @var Builder $q */
            foreach ([$sticky, $query] as $q) {
                $read = $q->newQuery()
                    ->selectRaw('1')
                    ->from('discussion_user as sticky')
                    ->whereColumn('sticky.discussion_id', 'id')
                    ->where('sticky.user_id', '=', $state->getActor()->id)
                    ->whereColumn('sticky.last_read_post_number', '>=', 'last_post_number');

                // Add the bindings manually (rather than as the second
                // argument in orderByRaw) for now due to a bug in Laravel which
                // would add the bindings in the wrong order.
                $q->selectRaw('(is_sticky and not exists ('.$read->toSql().') and last_posted_at > ?) as is_unread_sticky', array_merge($read->getBindings(), [$state->getActor()->marked_all_as_read_at ?: $epochTime]));
            }

            $query->union($sticky);

            $query->orderByDesc('is_unread_sticky');

            $query->unionOrders = array_merge($query->unionOrders ?? [], $query->orders ?? []);
            $query->unionLimit = $query->limit;
            $query->unionOffset = $query->offset;

            $query->limit = $sticky->limit = $query->offset + $query->limit;
            unset($query->offset, $sticky->offset);
        }
    }
}
