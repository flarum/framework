<?php namespace Flarum\Core\Permissions;

class PermissionRepository
{
    public function get()
    {
        return Permission::all();
    }

    public function save(Permission $permission)
    {
        $permission->assertValid();
        $permission->save();
    }

    public function delete(Permission $permission)
    {
        $permission->delete();
    }
}
