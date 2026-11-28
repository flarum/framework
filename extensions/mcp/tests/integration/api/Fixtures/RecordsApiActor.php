<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\api\Fixtures;

use Flarum\Http\RequestUtil;
use Flarum\Mcp\Tests\integration\api\ApiMiddlewareTest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RecordsApiActor implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        ApiMiddlewareTest::$seen[] = [RequestUtil::getActor($request)->id, $request->getAttribute('routeName'), $request->getAttribute('routeParameters')];

        return $handler->handle($request);
    }
}
