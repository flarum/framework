<?php namespace Flarum\Extend;

use Illuminate\Foundation\Application;
use Flarum\Core\Models\Permission as PermissionModel;

class Permission implements ExtenderInterface
{
    protected $permission;

    public function __construct($permission)
    {
        $this->permission = $permission;
    }

    public function extend(Application $app)
    {
        PermissionModel::addPermission($this->permission);
    }
}
