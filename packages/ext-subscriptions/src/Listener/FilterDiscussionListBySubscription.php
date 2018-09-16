<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listener;

use Flarum\Discussion\Event\Searching;
use Flarum\Event\ConfigureDiscussionGambits;
use Flarum\Subscriptions\Gambit\SubscriptionGambit;
use Illuminate\Contracts\Events\Dispatcher;

class FilterDiscussionListBySubscription
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureDiscussionGambits::class, [$this, 'addGambit']);
        $events->listen(Searching::class, [$this, 'filterIgnored']);
    }

    /**
     * @param ConfigureDiscussionGambits $event
     */
    public function addGambit(ConfigureDiscussionGambits $event)
    {
        $event->gambits->add(SubscriptionGambit::class);
    }

    /**
     * @param Searching $event
     */
    public function filterIgnored(Searching $event)
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
