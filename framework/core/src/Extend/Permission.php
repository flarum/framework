<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\User\Access\AdminPolicy;
use Illuminate\Contracts\Container\Container;

class Permission implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        // TODO: Implement extend() method.
    }

    public function allowsNoOne(string $permission): self
    {
        AdminPolicy::allowNoOneOnPermission($permission);

        return $this;
    }
}
