<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Middleware;

use Flarum\Audit\AuditLogger;
use Flarum\Group\Permission;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExtendSetPermissionController implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getUri()->getPath() !== '/permission') {
            return $handler->handle($request);
        }

        $body = $request->getParsedBody();
        $permission = Arr::get($body, 'permission');
        $newGroupIds = Arr::get($body, 'groupIds');

        // Get a copy of the old values before the change
        $oldGroupIds = Permission::query()->where('permission', $permission)->pluck('group_id')->all();

        $response = $handler->handle($request);

        // If the response is of type EmptyResponse, the change must have been successful
        if ($response instanceof EmptyResponse) {
            AuditLogger::log('permission_changed', [
                'permission' => $permission,
                'old_group_ids' => array_map('intval', $oldGroupIds),
                'new_group_ids' => array_map('intval', $newGroupIds),
            ]);
        }

        return $response;
    }
}
