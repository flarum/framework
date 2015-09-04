<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Mentions;

use Flarum\Support\Extension as BaseExtension;
use Illuminate\Events\Dispatcher;

class Extension extends BaseExtension
{
    public function listen(Dispatcher $events)
    {
        $events->subscribe('Flarum\Mentions\Listeners\AddClientAssets');
        $events->subscribe('Flarum\Mentions\Listeners\AddModelRelationships');
        $events->subscribe('Flarum\Mentions\Listeners\AddApiRelationships');
        $events->subscribe('Flarum\Mentions\Listeners\AddUserMentionsFormatter');
        $events->subscribe('Flarum\Mentions\Listeners\AddPostMentionsFormatter');
        $events->subscribe('Flarum\Mentions\Listeners\UpdateUserMentionsMetadata');
        $events->subscribe('Flarum\Mentions\Listeners\UpdatePostMentionsMetadata');
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../views', 'mentions');
    }
}
