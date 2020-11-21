<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Database\AbstractModel;
use Flarum\Database\ScopeVisibilityTrait;
use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Group\Access\ScopeGroupVisibility;

class GroupServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        $events = $this->app->make('events');
        $events->subscribe(GroupPolicy::class);

        Group::registerVisibilityScoper(Group::class, new ScopeGroupVisibility(), 'view');
    }
}
