<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky;

use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;
use Flarum\Tags\Query\TagFilterGambit;
use Flarum\Settings\SettingsRepositoryInterface;

class PinStickiedDiscussionsToTop
{
    protected $settings;

    public function __construct(SettingsRepositoryInterface $settings)
    {
        $this->settings = $settings;
    }

    public function __invoke(FilterState $filterState, QueryCriteria $criteria)
    {
        if ($criteria->sortIsDefault) {
            $query = $filterState->getQuery();
            $filterRead = $this->settings->get('flarum-sticky.filter_read_from_stickied');

            // If filter read from stickied is disabled, then pin all stickied
            // discussions to the top whether they are read or not.
            if (!$filterRead) {
                $this->pinStickiedToTop($query);

                return;
            }

            // If we are viewing a specific tag, then pin all stickied
            // discussions to the top no matter what.
            $filters = $filterState->getActiveFilters();

            if ($count = count($filters)) {
                if ($count === 1 && $filters[0] instanceof TagFilterGambit) {
                    $this->pinStickiedToTop($query);
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

            $query->union($sticky);

            $read = $query->newQuery()
                ->selectRaw('1')
                ->from('discussion_user as sticky')
                ->whereColumn('sticky.discussion_id', 'id')
                ->where('sticky.user_id', '=', $filterState->getActor()->id)
                ->whereColumn('sticky.last_read_post_number', '>=', 'last_post_number');

            // Add the bindings manually (rather than as the second
            // argument in orderByRaw) for now due to a bug in Laravel which
            // would add the bindings in the wrong order.
            $query->orderByRaw('is_sticky and not exists ('.$read->toSql().') and last_posted_at > ? desc')
                ->addBinding(array_merge($read->getBindings(), [$filterState->getActor()->marked_all_as_read_at ?: 0]), 'union');

            $query->unionOrders = array_merge($query->unionOrders, $query->orders);
            $query->unionLimit = $query->limit;
            $query->unionOffset = $query->offset;

            $query->limit = $sticky->limit = $query->offset + $query->limit;
            unset($query->offset, $sticky->offset);
        }
    }

    /**
     * Pin all stickied discussions to the top of the query.
     * This is done by prepending an order clause to the query.
     *
     * @param $query
     *
     * @return void
     */
    protected function pinStickiedToTop($query)
    {
        if (!is_array($query->orders)) {
            $query->orders = [];
        }

        array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);
    }
}
