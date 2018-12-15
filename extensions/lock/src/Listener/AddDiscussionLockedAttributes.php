<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Listener;

use Flarum\Api\Event\Serializing;
use Flarum\Api\Serializer\DiscussionSerializer;

class AddDiscussionLockedAttributes
{
    public function handle(Serializing $event)
    {
        if ($event->isSerializer(DiscussionSerializer::class)) {
            $event->attributes['isLocked'] = (bool) $event->model->is_locked;
            $event->attributes['canLock'] = (bool) $event->actor->can('lock', $event->model);
        }
    }
}
