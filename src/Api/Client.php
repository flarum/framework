<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Exception;
use Flarum\Foundation\ErrorHandling\Registry;
use Flarum\Http\Middleware as HttpMiddleware;
use Flarum\Http\RequestUtil;
use Flarum\Http\RouteCollection;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Client
{
    /**
     * @var MiddlewarePipeInterface
     */
    protected $pipe;

    /**
     * @param Container $container
     */
    public function __construct(MiddlewarePipeInterface $pipe)
    {
        $this->pipe = $pipe;
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string $routeName
     * @param User|null $actor
     * @param array $queryParams
     * @param array $body
     * @return ResponseInterface
     * @throws Exception
     */
    public function send(string $routeName, User $actor = null, array $queryParams = [], array $body = []): ResponseInterface
    {
        $request = ServerRequestFactory::fromGlobals(null, $queryParams, $body);
        $request = RequestUtil::withActor($request, $actor);
        $request = $request->withAttribute('routeName', $routeName);

        return $this->pipe->handle($request);
    }
}
