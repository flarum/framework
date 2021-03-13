<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;
use Flarum\Tags\Query\TagFilterGambit;

class PinStickiedDiscussionsToTop
{
    /**
     * Used to get the default sort.
     *
     * @var ListDiscussionsController
     */
    protected $listDiscussionsController;

    public function __construct(ListDiscussionsController $listDiscussionsController)
    {
        $this->listDiscussionsController = $listDiscussionsController;
    }

    public function __invoke(FilterState $filterState, QueryCriteria $criteria)
    {
        // TODO: This should take modifications by extensions into account.
        if ($criteria->sort == $this->listDiscussionsController->sort) {
            $query = $filterState->getQuery();

            // If we are viewing a specific tag, then pin all stickied
            // discussions to the top no matter what.
            $filters = $filterState->getActiveFilters();

            if ($count = count($filters)) {
                if ($count === 1 && $filters[0] instanceof TagFilterGambit) {
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
            $sticky->orders = null;

            $query->union($sticky);

            $read = $query->newQuery()
                ->selectRaw(1)
                ->from('discussion_user as sticky')
                ->whereColumn('sticky.discussion_id', 'id')
                ->where('sticky.user_id', '=', $filterState->getActor()->id)
                ->whereColumn('sticky.last_read_post_number', '>=', 'last_post_number');

            // Add the bindings manually (rather than as the second
            // argument in orderByRaw) for now due to a bug in Laravel which
            // would add the bindings in the wrong order.
            $query->orderByRaw('is_sticky and not exists ('.$read->toSql().') and last_posted_at > ? desc')
                ->addBinding(array_merge($read->getBindings(), [$filterState->getActor()->read_time ?: 0]), 'union');

            $query->unionOrders = array_merge($query->unionOrders, $query->orders);
            $query->unionLimit = $query->limit;
            $query->unionOffset = $query->offset;

            $query->limit = $sticky->limit = $query->offset + $query->limit;
            $query->offset = $sticky->offset = null;
        }
    }
}
