<?php

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;

class UserPreferences implements ExtenderInterface
{
    public function extend(Container $container, Extension $extension = null)
    {
        // TODO: Implement extend() method.
    }

    public function add(string $key, callable $transformer = null, $default = null)
    {
        User::addPreference($key, $transformer, $default);

        return $this;
    }
}
