<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tags;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Tags\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Tags\Listeners\AddModelRelationship');
        $events->subscribe('Flarum\Tags\Listeners\ConfigureDiscussionPermissions');
        $events->subscribe('Flarum\Tags\Listeners\ConfigureTagPermissions');
        $events->subscribe('Flarum\Tags\Listeners\AddApiAttributes');
        $events->subscribe('Flarum\Tags\Listeners\PersistData');
        $events->subscribe('Flarum\Tags\Listeners\LogDiscussionTagged');
        $events->subscribe('Flarum\Tags\Listeners\UpdateTagMetadata');
        $events->subscribe('Flarum\Tags\Listeners\AddTagGambit');
    }

    public function boot()
    {
        Tag::setValidator($this->app->make('validator'));
    }
}
