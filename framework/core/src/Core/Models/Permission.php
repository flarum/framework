<?php namespace Flarum\Core\Models;

class Permission extends Model
{
    protected static $permissions = [];

    public static function getPermissions()
    {
        return static::$permissions;
    }

    public static function addPermission($permission)
    {
        static::$permissions[] = $permission;
    }
}
