<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Listeners;

use Flarum\Events\ApiAttributes;
use Flarum\Events\RegisterApiRoutes;
use Illuminate\Contracts\Events\Dispatcher;
use Flarum\Api\Serializers\ForumSerializer;

class AddApiAttributes
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ApiAttributes::class, [$this, 'addAttributes']);
        $events->listen(RegisterApiRoutes::class, [$this, 'addRoutes']);
    }

    public function addAttributes(ApiAttributes $event)
    {
        if ($event->serializer instanceof ForumSerializer) {
            $event->attributes['pusherKey'] = app('Flarum\Core\Settings\SettingsRepository')->get('pusher.app_key');
        }
    }

    public function addRoutes(RegisterApiRoutes $event)
    {
        $event->post('/pusher/auth', 'pusher.auth', 'Flarum\Pusher\Api\AuthAction');
    }
}
