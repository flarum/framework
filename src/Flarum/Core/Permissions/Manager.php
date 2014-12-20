<?php namespace Flarum\Core\Permissions;

class Manager
{
    protected $map;

    protected $permissions;

    public function __construct(PermissionRepository $permissions)
    {
        $this->permissions = $permissions;
    }

    public function getMap()
    {
        if (is_null($this->map)) {
            $permissions = $this->permissions->get();
            foreach ($permissions as $permission) {
                $this->map[$permission->entity.'.'.$permission->permission][] = $permission->grantee;
            }
        }

        return $this->map;
    }

    public function granted($user, $permission, $entity)
    {
        $grantees = $user->getGrantees();

        // If user has admin, then yes!
        if (in_array('group.1', $grantees)) {
            return true;
        }

        $permission = $entity.'.'.$permission;

        $map = $this->getMap();
        $mappedGrantees = isset($map[$permission]) ? $map[$permission] : [];

        return (bool) array_intersect($grantees, $mappedGrantees);
    }
}
