<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\Database\AbstractModel;
use Flarum\User\User;

abstract class AbstractPolicy
{
    public const GLOBAL = 'GLOBAL';
    public const ALLOW = 'ALLOW';
    public const DENY = 'DENY';
    public const FORCE_ALLOW = 'FORCE_ALLOW';
    public const FORCE_DENY = 'FORCE_DENY';

    protected function allow(): string
    {
        return static::ALLOW;
    }

    protected function deny(): string
    {
        return static::DENY;
    }

    protected function forceAllow(): string
    {
        return static::FORCE_ALLOW;
    }

    protected function forceDeny(): string
    {
        return static::FORCE_DENY;
    }

    public function checkAbility(User $actor, string $ability, string|AbstractModel|null $instance): ?string
    {
        // If a specific method for this ability is defined,
        // call that and return any non-null results
        if (method_exists($this, $ability)) {
            $result = $this->sanitizeResult(call_user_func_array([$this, $ability], [$actor, $instance]));

            if (! is_null($result)) {
                return $result;
            }
        }

        // If a "total access" method is defined, try that.
        if (method_exists($this, 'can')) {
            return $this->sanitizeResult(call_user_func_array([$this, 'can'], [$actor, $ability, $instance]));
        }

        return null;
    }

    /**
     * Allows `true` to be used in place of `->allow()`, and `false` instead of `->deny()`
     * This allows more concise and intuitive code, by returning boolean statements:.
     *
     * WITHOUT THIS:
     * `return SOME_BOOLEAN_LOGIC ? $this->allow() : $this->deny();
     *
     * WITH THIS:
     * `return SOME_BOOLEAN_LOGIC;
     */
    public function sanitizeResult(string|bool|null $result): ?string
    {
        if ($result === true) {
            return $this->allow();
        } elseif ($result === false) {
            return $this->deny();
        }

        return $result;
    }
}
