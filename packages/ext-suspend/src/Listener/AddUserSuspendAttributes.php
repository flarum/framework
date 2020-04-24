<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\UserSerializer;
use Illuminate\Contracts\Events\Dispatcher;

class AddUserSuspendAttributes
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
        if ($event->isSerializer(UserSerializer::class)) {
            $canSuspend = $event->actor->can('suspend', $event->model);

            if ($canSuspend) {
                $event->attributes['suspendedUntil'] = $event->formatDate($event->model->suspended_until);
            }

            $event->attributes['canSuspend'] = $canSuspend;
        }
    }
}
