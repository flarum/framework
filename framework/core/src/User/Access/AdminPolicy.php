<?php

namespace Flarum\User\Access;

use Flarum\User\User;

class AdminPolicy extends AbstractPolicy
{
    static array $allowNoOnePermissions = [];

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
