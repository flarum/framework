<?php namespace Flarum\Core\Support;

use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Models\User;
use Closure;

trait Locked
{
    protected static $conditions = [];

    protected static function getConditions($action)
    {
        $conditions = isset(static::$conditions[$action]) ? static::$conditions[$action] : [];
        $all = isset(static::$conditions['*']) ? static::$conditions['*'] : [];

        return array_merge($conditions, $all);
    }

    public static function allow($action, Closure $condition)
    {
        foreach ((array) $action as $action) {
            if (! isset(static::$conditions[$action])) {
                static::$conditions[$action] = [];
            }

            static::$conditions[$action][] = $condition;
        }
    }

    public function can(User $user, $action)
    {
        foreach ($this->getConditions($action) as $condition) {
            $can = $condition($this, $user, $action);

            if ($can !== null) {
                return $can;
            }
        }
    }

    /**
     * Assert that the user has a certain permission for this model, throwing
     * an exception if they don't.
     *
     * @param \Flarum\Core\Models\User $user
     * @param string $permission
     * @return void
     *
     * @throws \Flarum\Core\Exceptions\PermissionDeniedException
     */
    public function assertCan(User $user, $action)
    {
        if (! $this->can($user, $action)) {
            throw new PermissionDeniedException;
        }
    }
}
