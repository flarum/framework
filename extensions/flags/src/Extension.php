<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;
use Flarum\Core\Posts\Post;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Flags\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Flags\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Flags\Listeners\AddModelRelationship');
    }
}
