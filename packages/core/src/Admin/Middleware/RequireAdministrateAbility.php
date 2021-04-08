<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\Http\RequestUtil;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class RequireAdministrateAbility implements Middleware
{
    public function process(Request $request, Handler $handler): Response
    {
        RequestUtil::getActor($request)->assertAdmin();

        return $handler->handle($request);
    }
}
