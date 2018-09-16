<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listener;

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Event\ConfigureModelDates;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

class AddUserSuspendAttributes
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureModelDates::class, [$this, 'addDates']);
        $events->listen(Serializing::class, [$this, 'addAttributes']);
    }

    /**
     * @param ConfigureModelDates $event
     */
    public function addDates(ConfigureModelDates $event)
    {
        if ($event->isModel(User::class)) {
            $event->dates[] = 'suspended_until';
        }
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
