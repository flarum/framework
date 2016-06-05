<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Embed\Listener;

use Flarum\Event\ConfigureForumRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class AddEmbedRoute
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureForumRoutes::class, [$this, 'addEmbedRoute']);
    }

    /**
     * @param ConfigureForumRoutes $event
     */
    public function addEmbedRoute(ConfigureForumRoutes $event)
    {
        $event->get('/embed/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]', 'embed.discussion', 'Flarum\Embed\EmbeddedDiscussionController');
    }
}
