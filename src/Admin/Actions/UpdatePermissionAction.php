<?php namespace Flarum\Admin\Actions;

use Psr\Http\Message\ServerRequestInterface as Request;
use Flarum\Support\Action;
use Flarum\Core\Groups\Permission;

class UpdatePermissionAction extends Action
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $input = $request->getAttributes();
        $permission = array_get($input, 'permission');
        $groupIds = array_get($input, 'groupIds');

        Permission::where('permission', $permission)->delete();

        Permission::insert(array_map(function ($groupId) use ($permission) {
            return [
                'permission' => $permission,
                'group_id' => $groupId
            ];
        }, $groupIds));

        return $this->success();
    }
}
