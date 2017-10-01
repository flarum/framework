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

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\DiscussionSerializer;
use Illuminate\Contracts\Events\Dispatcher;

class AddDiscussionSubscriptionAttribute
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Serializing::class, [$this, 'addAttributes']);
    }

    /**
     * @param Serializing $event
     */
    public function addAttributes(Serializing $event)
    {
        if ($event->isSerializer(DiscussionSerializer::class)
            && ($state = $event->model->state)) {
            $event->attributes['subscription'] = $state->subscription ?: false;
        }
    }
}
