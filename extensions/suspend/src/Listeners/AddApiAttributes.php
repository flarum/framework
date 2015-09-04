<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Suspend\Listeners;

use Flarum\Events\ModelDates;
use Flarum\Events\ApiAttributes;
use Flarum\Core\Users\User;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\UserSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ModelDates::class, [$this, 'addDates']);
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
    }

    public function addDates(ModelDates $event)
    {
        if ($event->model instanceof User) {
            $event->dates[] = 'suspend_until';
        }
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof UserSerializer) {
            $canSuspend = $event->model->can($event->actor, 'suspend');

            if ($canSuspend) {
                $suspendUntil = $event->model->suspend_until;

                $event->attributes['suspendUntil'] = $suspendUntil ? $suspendUntil->toRFC3339String() : null;
            }

            $event->attributes['canSuspend'] = $canSuspend;
        }
    }
}
