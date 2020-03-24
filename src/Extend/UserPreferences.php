<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;

class UserPreferences implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        // There's nothing here as the logic is contained in the `add()` method directly.
    }

    public function add(string $key, callable $transformer = null, $default = null)
    {
        User::addPreference($key, $transformer, $default);

        return $this;
    }
}
