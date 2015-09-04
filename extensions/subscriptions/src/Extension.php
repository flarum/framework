<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Subscriptions;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Subscriptions\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Subscriptions\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Subscriptions\Listeners\PersistSubscriptionData');
        $events->subscribe('Flarum\Subscriptions\Listeners\NotifyNewPosts');
        $events->subscribe('Flarum\Subscriptions\Listeners\HideIgnoredDiscussions');
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'subscriptions');
    }
}
