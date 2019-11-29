<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Sticky\Listener;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Api\Event\Serializing;
use Flarum\Api\Event\WillGetData;
use Flarum\Api\Serializer\DiscussionSerializer;
use Illuminate\Contracts\Events\Dispatcher;

class AddApiAttributes
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Serializing::class, [$this, 'prepareApiAttributes']);
        $events->listen(WillGetData::class, [$this, 'includeFirstPost']);
    }

    /**
     * @param Serializing $event
     */
    public function prepareApiAttributes(Serializing $event)
    {
        if ($event->isSerializer(DiscussionSerializer::class)) {
            $event->attributes['isSticky'] = (bool) $event->model->is_sticky;
            $event->attributes['canSticky'] = (bool) $event->actor->can('sticky', $event->model);
        }
    }

    /**
     * @param WillGetData $event
     */
    public function includeFirstPost(WillGetData $event)
    {
        if ($event->isController(ListDiscussionsController::class)) {
            $event->addInclude('firstPost');
        }
    }
}
