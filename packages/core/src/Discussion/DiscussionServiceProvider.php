<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Discussion\Access\ScopeDiscussionVisibility;
use Flarum\Discussion\Event\Renamed;
use Flarum\Foundation\AbstractServiceProvider;
use Illuminate\Contracts\Events\Dispatcher;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    public function boot(Dispatcher $events)
    {
        $events->subscribe(DiscussionMetadataUpdater::class);

        $events->listen(
            Renamed::class,
            DiscussionRenamedLogger::class
        );

        Discussion::registerVisibilityScoper(new ScopeDiscussionVisibility(), 'view');
    }
}
