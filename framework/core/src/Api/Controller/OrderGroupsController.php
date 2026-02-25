<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Group\Group;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class OrderGroupsController implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $order = Arr::get($request->getParsedBody(), 'order');

        if ($order === null || ! is_array($order)) {
            return new EmptyResponse(422);
        }

        Group::query()->update(['position' => null]);

        foreach ($order as $position => $id) {
            Group::where('id', $id)->update(['position' => $position]);
        }

        return new EmptyResponse(204);
    }
}
