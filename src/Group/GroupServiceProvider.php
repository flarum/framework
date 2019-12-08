<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Foundation\AbstractServiceProvider;

class GroupServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $events = $this->app->make('events');
        $events->subscribe(GroupPolicy::class);
    }
}
