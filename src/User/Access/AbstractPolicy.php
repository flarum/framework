<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\User\User;

abstract class AbstractPolicy
{
    public const GLOBAL = 'GLOBAL';
    public const ALLOW = 'ALLOW';
    public const DENY = 'DENY';
    public const FORCE_ALLOW = 'FORCE_ALLOW';
    public const FORCE_DENY = 'FORCE_DENY';

    protected function allow()
    {
        return static::ALLOW;
    }

    protected function deny()
    {
        return static::DENY;
    }

    protected function forceAllow()
    {
        return static::FORCE_ALLOW;
    }

    protected function forceDeny()
    {
        return static::FORCE_DENY;
    }

    /**
     * @param User $user
     * @param string $ability
     * @param $instance
     * @return bool|void
     */
    public function checkAbility(User $actor, string $ability, $instance)
    { // If a specific method for this ability is defined,
        // call that and return any non-null results
        if (method_exists($this, $ability)) {
            $result = call_user_func_array([$this, $ability], [$actor, $instance]);

            if (! is_null($result)) {
                return $result;
            }
        }

        // If a "total access" method is defined, try that.
        if (method_exists($this, 'can')) {
            return call_user_func_array([$this, 'can'], [$actor, $ability, $instance]);
        }
    }
}
