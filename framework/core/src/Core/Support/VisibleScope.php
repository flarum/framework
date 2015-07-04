<?php namespace Flarum\Core\Support;

use Flarum\Core\Users\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Add a query scope to an Eloquent model that filters out records that a user
 * is not allowed to view.
 */
trait VisibleScope
{
    /**
     * @var callable[]
     */
    protected static $visibleScopes = [];

    /**
     * Add a callback to scope a query to only include records that are visible
     * to a user.
     *
     * @param callable $scope
     */
    public static function addVisibleScope(callable $scope)
    {
        static::$visibleScopes[] = $scope;
    }

    /**
     * Scope a query to only include records that are visible to a user.
     *
     * @param Builder $query
     * @param User $user
     */
    public function scopeWhereVisibleTo(Builder $query, User $user)
    {
        foreach (static::$visibleScopes as $scope) {
            $scope($query, $user);
        }
    }
}
