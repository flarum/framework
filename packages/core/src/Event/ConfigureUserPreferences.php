<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Flarum\User\User;

/**
 * @deprecated beta 15, removed beta 16
 */
class ConfigureUserPreferences
{
    public function add($key, callable $transformer = null, $default = null)
    {
        User::addPreference($key, $transformer, $default);
    }
}
