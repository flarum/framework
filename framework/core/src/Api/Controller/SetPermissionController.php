<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Group\Permission;
use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class SetPermissionController extends AbstractController
{
    public function __invoke(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $permission = $request->json('permission');
        $groupIds = $request->json('groupIds');

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
