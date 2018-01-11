<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Discussion;

use Flarum\Foundation\AbstractServiceProvider;

class DiscussionServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $events = $this->app->make('events');

        $events->subscribe(DiscussionMetadataUpdater::class);
        $events->subscribe(DiscussionPolicy::class);
        $events->subscribe(DiscussionRenamedLogger::class);
    }
}
