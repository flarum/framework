<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Group\Permission;
use Flarum\Group\PermissionCache;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetPermissionController implements RequestHandlerInterface
{
    public function __construct(
        protected PermissionCache $permissionCache
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $body = $request->getParsedBody();
        $permission = Arr::get($body, 'permission');
        $groupIds = Arr::get($body, 'groupIds');

        Permission::where('permission', $permission)->delete();

        Permission::insert(array_map(function ($groupId) use ($permission) {
            return [
                'permission' => $permission,
                'group_id' => $groupId
            ];
        }, $groupIds));

        // These writes go through the query builder, so no model events fire —
        // reset the shared permission cache explicitly.
        $this->permissionCache->flush();

        return new EmptyResponse(204);
    }
}
