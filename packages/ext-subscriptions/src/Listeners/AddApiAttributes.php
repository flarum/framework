<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions\Listeners;

use Flarum\Events\ApiAttributes;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\DiscussionSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof DiscussionSerializer &&
            ($state = $event->model->state)) {
            $event->attributes['subscription'] = $state->subscription ?: false;
        }
    }
}
