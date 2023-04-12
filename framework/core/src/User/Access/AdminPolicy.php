<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Access;

use Flarum\User\User;

class AdminPolicy extends AbstractPolicy
{
    public static array $allowNoOnePermissions = [];

    /**
     * @param string|array<string> $permissions
     * @return void
     */
    public static function allowNoOneOnPermission($permissions)
    {
        self::$allowNoOnePermissions[] = array_merge(
            static::$allowNoOnePermissions,
            (array) $permissions
        );
    }

    /**
     * @param User $actor
     * @param string $ability
     * @return bool|null
     */
    public function can(User $actor, $ability)
    {
        if (! in_array($ability, static::$allowNoOnePermissions)
            && $actor->isAdmin()) {
            return $this->forceAllow();
        }
    }
}
