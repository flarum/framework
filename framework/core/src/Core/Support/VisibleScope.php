<?php namespace Flarum\Core\Support;

trait VisibleScope
{
    protected static $visibleScopes = [];

    public static function addVisibleScope(callable $scope)
    {
        static::$visibleScopes[] = $scope;
    }

    public function scopeWhereVisibleTo($query, $user)
    {
        foreach (static::$visibleScopes as $scope) {
            $scope($query, $user);
        }
    }
}
