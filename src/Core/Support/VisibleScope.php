<?php namespace Flarum\Core\Support;

use Flarum\Core\Models\User;

trait VisibleScope
{
    protected static $visibleScopes = [];

    public static function scopeVisible($scope)
    {
        static::$visibleScopes[] = $scope;
    }

    public function scopeWhereVisibleTo($query, User $user)
    {
        foreach (static::$visibleScopes as $scope) {
            $scope($query, $user);
        }
    }
}
