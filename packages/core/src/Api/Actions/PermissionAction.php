<?php namespace Flarum\Api\Actions;

use Flarum\Api\Request;
use Flarum\Core\Groups\Permission;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Zend\Diactoros\Response\EmptyResponse;

class PermissionAction implements Action
{
    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, array $routeParams = [])
    {
        if (! $request->actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        $permission = $request->get('permission');
        $groupIds = $request->get('groupIds');

        Permission::where('permission', $permission)->delete();

        Permission::insert(array_map(function ($groupId) use ($permission) {
            return [
                'permission' => $permission,
                'group_id' => $groupId
            ];
        }, $groupIds));

        return new EmptyResponse(204);
    }
}
