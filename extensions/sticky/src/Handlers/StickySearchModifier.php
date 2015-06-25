<?php namespace Flarum\Sticky\Handlers;

use Flarum\Core\Events\DiscussionSearchWillBePerformed;
use Flarum\Tags\TagGambit;

class StickySearchModifier
{
    public function subscribe($events)
    {
        $events->listen('Flarum\Core\Events\DiscussionSearchWillBePerformed', __CLASS__.'@reorderSearch');
    }

    public function reorderSearch(DiscussionSearchWillBePerformed $event)
    {
        if ($event->criteria->sort === null) {
            $query = $event->searcher->query();

            if (!is_array($query->orders)) {
                $query->orders = [];
            }

            foreach ($event->searcher->getActiveGambits() as $gambit) {
                if ($gambit instanceof TagGambit) {
                    array_unshift($query->orders, ['column' => 'is_sticky', 'direction' => 'desc']);
                    return;
                }
            }

            $query->leftJoin('users_discussions', function ($join) use ($event) {
                $join->on('users_discussions.discussion_id', '=', 'discussions.id')
                     ->where('discussions.is_sticky', '=', true)
                     ->where('users_discussions.user_id', '=', $event->criteria->user->id);
            });
            // might be quicker to do a subquery in the order clause than a join?
            array_unshift(
                $query->orders,
                ['type' => 'raw', 'sql' => '(is_sticky AND (users_discussions.read_number IS NULL OR discussions.last_post_number > users_discussions.read_number)) desc']
            );
        }
    }
}
