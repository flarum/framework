<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ContainerUtil;
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
     *
     * The callable should accept:
     * - $user: An instance of the User model.
     * - $password: A string.
     *
     * The callable should return:
     * - `true` if the given password is valid.
     * - `null` (or not return anything) if the given password is invalid, or this checker does not apply.
     *           Generally, `null` should be returned instead of `false` so that other
     *           password checkers can run.
     * - `false` if the given password is invalid, and no other checkers should be considered.
     *            Evaluation will be immediately halted if any checkers return `false`.
     *
     * @return self
     */
    public function addPasswordChecker(string $identifier, $callback): self
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
    public function removePasswordChecker(string $identifier): self
    {
        $this->removePasswordCheckers[] = $identifier;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.user.password_checkers', function ($passwordCheckers) use ($container) {
            foreach ($this->removePasswordCheckers as $identifier) {
                if (array_key_exists($identifier, $passwordCheckers)) {
                    unset($passwordCheckers[$identifier]);
                }
            }

            foreach ($this->addPasswordCheckers as $identifier => $checker) {
                $passwordCheckers[$identifier] = ContainerUtil::wrapCallback($checker, $container);
            }

            return $passwordCheckers;
        });
    }
}
