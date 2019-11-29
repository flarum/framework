<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Discussion\Event\Searching;

class FilterDiscussionListBySubscription
{
    public function handle(Searching $event)
    {
        if (! $event->criteria->query) {
            // might be better as `id IN (subquery)`?
            $actor = $event->search->getActor();
            $event->search->getQuery()->whereNotExists(function ($query) use ($actor) {
                $query->selectRaw(1)
                      ->from('discussion_user')
                      ->whereColumn('discussions.id', 'discussion_id')
                      ->where('user_id', $actor->id)
                      ->where('subscription', 'ignore');
            });
        }
    }
}
