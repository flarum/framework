<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Discussion\Event\Searching;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Sticky\Gambit\StickyGambit;
use Flarum\Tags\Gambit\TagGambit;
use Illuminate\Contracts\Events\Dispatcher;

class PinStickiedDiscussionsToTop
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addStickyGambit']);
        $events->listen(Searching::class, [$this, 'reorderSearch']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addStickyGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(StickyGambit::class);
    }

    /**
     * @param Searching $event
     */
    public function reorderSearch(Searching $event)
    {
        if ($event->criteria->sort === null) {
            $search = $event->search;
            $query = $search->getQuery();

            // If we are viewing a specific tag, then pin all stickied
            // discussions to the top no matter what.
            $gambits = $search->getActiveGambits();

            if ($count = count($gambits)) {
                if ($count === 1 && $gambits[0] instanceof TagGambit) {
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
                ->where('sticky.user_id', '=', $search->getActor()->id)
                ->whereColumn('sticky.last_read_post_number', '>=', 'last_post_number');

            // Add the bindings manually (rather than as the second
            // argument in orderByRaw) for now due to a bug in Laravel which
            // would add the bindings in the wrong order.
            $query->orderByRaw('is_sticky and not exists ('.$read->toSql().') and last_posted_at > ? desc')
                ->addBinding(array_merge($read->getBindings(), [$search->getActor()->read_time ?: 0]), 'union');

            $query->unionOrders = array_merge($query->unionOrders, $query->orders);
            $query->unionLimit = $query->limit;
            $query->unionOffset = $query->offset;

            $query->limit = $sticky->limit = $query->offset + $query->limit;
            $query->offset = $sticky->offset = null;
        }
    }
}
