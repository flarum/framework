<?php namespace Flarum\Core\Support;

use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Core\Users\User;

/**
 * 'Lock' an object, allowing the permission of a user to perform an action to
 * be tested.
 */
trait Locked
{
    /**
     * @var callable[]
     */
    protected static $conditions = [];

    /**
     * Get the condition callbacks for the specified action.
     *
     * @param string $action
     * @return callable[]
     */
    protected static function getConditions($action)
    {
        $conditions = array_get(static::$conditions, $action, []);
        $all = array_get(static::$conditions, '*', []);

        return array_merge($conditions, $all);
    }

    /**
     * Allow the specified action if the given condition is satisfied.
     *
     * @param string $action
     * @param callable $condition The condition callback. Parameters are the
     *     object that is locked, the user performing the action,
     *     and the name of the action. This condition will be ignored if it
     *     returns null; otherwise, the return value will determine whether or
     *     not the action is allowed.
     */
    public static function allow($action, callable $condition)
    {
        foreach ((array)$action as $action) {
            static::$conditions[$action][] = $condition;
        }
    }

    /**
     * Check whether or not a user has permission to perform an action,
     * according to the collected conditions.
     *
     * @param User $actor
     * @param string $action
     * @return bool
     */
    public function can(User $actor, $action)
    {
        foreach ($this->getConditions($action) as $condition) {
            $can = $condition($this, $actor, $action);

            if ($can !== null) {
                return $can;
            }
        }

        return false;
    }

    /**
     * Assert that the user has a certain permission for this model, throwing
     * an exception if they don't.
     *
     * @param User $actor
     * @param string $action
     * @throws PermissionDeniedException
     */
    public function assertCan(User $actor, $action)
    {
        if (! $this->can($actor, $action)) {
            throw new PermissionDeniedException;
        }
    }
}
