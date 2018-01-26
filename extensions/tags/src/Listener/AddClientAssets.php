<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags\Listener;

use Flarum\Event\ConfigureForumRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class AddClientAssets
{
    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(ConfigureForumRoutes::class, [$this, 'addRoutes']);
    }

    /**
     * @param ConfigureForumRoutes $routes
     */
    public function addRoutes(ConfigureForumRoutes $routes)
    {
        $routes->get('/t/{slug}', 'tag');
        $routes->get('/tags', 'tags');
    }
}
