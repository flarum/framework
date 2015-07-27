<?php namespace Flarum\Sticky\Listeners;

use Flarum\Events\RegisterDiscussionGambits;
use Flarum\Events\DiscussionSearchWillBePerformed;
use Flarum\Tags\Gambits\TagGambit;
use Illuminate\Contracts\Events\Dispatcher;

class PinStickiedDiscussionsToTop
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(RegisterDiscussionGambits::class, [$this, 'registerStickyGambit']);
        $events->listen(DiscussionSearchWillBePerformed::class, [$this, 'reorderSearch']);
    }

    public function registerStickyGambit(RegisterDiscussionGambits $event)
    {
        $event->gambits->add('Flarum\Sticky\Gambits\StickyGambit');
    }

    public function reorderSearch(DiscussionSearchWillBePerformed $event)
    {
        if ($event->criteria->sort === null) {
            $query = $event->search->getQuery();

            if (! is_array($query->orders)) {
                $query->orders = [];
            }

            foreach ($event->search->getActiveGambits() as $gambit) {
                if ($gambit instanceof TagGambit) {
                    array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);
                    return;
                }
            }

            $query->leftJoin('users_discussions', function ($join) use ($event) {
                $join->on('users_discussions.discussion_id', '=', 'discussions.id')
                     ->where('discussions.is_sticky', '=', true)
                     ->where('users_discussions.user_id', '=', $event->search->getActor()->id);
            });
            // might be quicker to do a subquery in the order clause than a join?
            array_unshift(
                $query->orders,
                ['type' => 'raw', 'sql' => '(is_sticky AND (users_discussions.read_number IS NULL OR discussions.last_post_number > users_discussions.read_number)) desc']
            );
        }
    }
}
