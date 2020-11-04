<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class Auth implements ExtenderInterface
{
    private $addPasswordCheckers = [];
    private $removePasswordCheckers = [];

    /**
     * Add a new password checker.
     *
     * @param string $identifier: Unique identifier for password checker.
     * @param callable|string $callback: A closure or invokable class that contains the logic of the password checker.
     *                                 It should return one of:
     *                                   - `false`: This marks the password as NOT VALID. It overrides all other outputs
     *                                   - `true`: This marks the password as VALID.
     *                                 All other outputs will be ignored.
     * @return self
     */
    public function addPasswordChecker(string $identifier, $callback)
    {
        $this->addPasswordCheckers[$identifier] = $callback;

        return $this;
    }

    /**
     * Remove a password checker.
     *
     * @param string $identifier: The unique identifier of the password checker to remove.
     * @return self
     */
    public function removePasswordChecker(string $identifier)
    {
        $this->removePasswordCheckers[] = $identifier;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.user.password_checkers', function ($passwordCheckers) {
            foreach ($this->removePasswordCheckers as $identifier) {
                if (array_key_exists($identifier, $passwordCheckers)) {
                    unset($passwordCheckers[$identifier]);
                }
            }

            return array_merge($passwordCheckers, $this->addPasswordCheckers);
        });
    }
}
