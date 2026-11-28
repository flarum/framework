<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\api\Fixtures;

use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RefusesRequests implements MiddlewareInterface
{
    public static int $status = 422;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return new JsonResponse(['errors' => [
            ['status' => (string) static::$status, 'detail' => 'Refused by middleware.', 'source' => ['pointer' => '/data/attributes/title']],
        ]], static::$status);
    }
}
