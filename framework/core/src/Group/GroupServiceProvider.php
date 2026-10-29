<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group;

use Flarum\Foundation\AbstractServiceProvider;
use Flarum\Group\Access\ScopeGroupVisibility;
use Illuminate\Contracts\Container\Container;

class GroupServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(PermissionCache::class);
    }

    public function boot(Container $container): void
    {
        Group::registerVisibilityScoper(new ScopeGroupVisibility(), 'view');

        // Keep the shared permission cache in sync with model-level permission
        // changes. (The admin permission endpoint writes through the query
        // builder and flushes the cache itself.)
        Permission::saved(fn (Permission $permission) => $container->make(PermissionCache::class)->forgetGroup($permission->group_id));
        Permission::deleted(fn (Permission $permission) => $container->make(PermissionCache::class)->forgetGroup($permission->group_id));

        // A deleted group's memberships cascade away, so fresh users get a new
        // group set (and cache key) anyway — but drop its cached sets so its
        // permissions cannot be served to any lingering instances.
        Group::deleted(fn (Group $group) => $container->make(PermissionCache::class)->forgetGroup($group->id));
    }
}
