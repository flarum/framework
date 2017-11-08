<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Event\ConfigureDiscussionSearch;
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
        $events->listen(ConfigureDiscussionSearch::class, [$this, 'reorderSearch']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addStickyGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(StickyGambit::class);
    }

    /**
     * @param ConfigureDiscussionSearch $event
     */
    public function reorderSearch(ConfigureDiscussionSearch $event)
    {
        if ($event->criteria->sort === null) {
            $search = $event->search;
            $query = $search->getQuery();

            // TODO: ideally we might like to consider an event in core that is
            // fired before the sort criteria is applied to the query (ie.
            // immediately after gambits are applied) so that we can add the
            // following order logic to the start without using array_unshift.

            if (! is_array($query->orders)) {
                $query->orders = [];
            }

            // If we are viewing a specific tag, then pin all stickied
            // discussions to the top no matter what.
            foreach ($search->getActiveGambits() as $gambit) {
                if ($gambit instanceof TagGambit) {
                    array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);

                    return;
                }
            }

            // Otherwise, if we are viewing "all discussions" or similar, only
            // pin stickied discussions to the top if they are unread. To do
            // this we construct an order clause containing a subquery which
            // determines whether or not the discussion is unread.
            $subquery = $query->newQuery()
                ->selectRaw(1)
                ->from('users_discussions as sticky')
                ->whereRaw('sticky.discussion_id = discussions.id')
                ->where('sticky.user_id', '=', $search->getActor()->id)
                ->where(function ($query) {
                    $query->whereNull('sticky.read_number')
                        ->orWhereRaw('discussions.last_post_number > sticky.read_number');
                })
                ->where('discussions.last_time', '>', $search->getActor()->read_time ?: 0);

            array_unshift($query->orders, [
                'type' => 'raw',
                'sql' => "(is_sticky and exists ({$subquery->toSql()})) desc"
            ]);

            $orderBindings = $query->getRawBindings()['order'];
            $query->setBindings(array_merge($subquery->getBindings(), $orderBindings), 'order');
        }
    }
}
