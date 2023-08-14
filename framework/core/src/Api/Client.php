<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Foundation\Config;
use Flarum\Http\RequestUtil;
use Flarum\Http\Router;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Pipeline;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Client
{
    protected ?User $actor = null;
    protected ?Request $parent = null;
    protected array $queryParams = [];
    protected array $body = [];

    public function __construct(
        protected array $middlewareStack,
        protected Container $container
    ) {
    }

    /**
     * Set the request actor.
     * This is not needed if a parent request is provided.
     * It can, however, override the parent request's actor.
     */
    public function withActor(User $actor): Client
    {
        $new = clone $this;
        $new->actor = $actor;

        return $new;
    }

    public function withParentRequest(ServerRequestInterface|Request $parent): Client
    {
        $new = clone $this;

        // Convert the PSR-7 request to an Illuminate request.
        if ($parent instanceof ServerRequestInterface) {
            $parent = RequestUtil::toIlluminate($parent);
        }

        $new->parent = $parent;

        return $new;
    }

    public function withQueryParams(array $queryParams): Client
    {
        $new = clone $this;
        $new->queryParams = $queryParams;

        return $new;
    }

    public function withBody(array $body): Client
    {
        $new = clone $this;
        $new->body = $body;

        return $new;
    }

    public function get(string $path): JsonResponse
    {
        return $this->send('GET', $path);
    }

    public function post(string $path): JsonResponse
    {
        return $this->send('POST', $path);
    }

    public function put(string $path): JsonResponse
    {
        return $this->send('PUT', $path);
    }

    public function patch(string $path): JsonResponse
    {
        return $this->send('PATCH', $path);
    }

    public function delete(string $path): JsonResponse
    {
        return $this->send('DELETE', $path);
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @internal
     */
    public function send(string $method, string $path): JsonResponse
    {
        $parent = $this->parent ?: Request::createFromGlobals();
        /** @var Config $config */
        $config = $this->container->make(Config::class);

        $symfonyRequest = SymfonyRequest::create(
            $config->path('api').$path, $method, $this->queryParams, $parent->cookies->all(), $parent->files->all(), $parent->server->all(), json_encode($this->body)
        );

        $request = Request::createFromBase($symfonyRequest);

        if ($this->parent) {
            $request->attributes->set('session', $this->parent->attributes->get('session'));
            $request = RequestUtil::withActor($request, RequestUtil::getActor($this->parent));
        }

        // This should override the actor from the parent request, if one exists.
        if ($this->actor) {
            $request = RequestUtil::withActor($request, $this->actor);
        }

        $originalRequest = $this->container->make('request');

        return (new Pipeline($this->container))
            ->send($request)
            ->then(function (Request $request) use ($originalRequest) {
                $this->container->instance('request', $request);

                /** @var Router $router */
                $router = $this->container->make(Router::class);

                $originalMiddlewareGroup = $router->getMiddlewareGroups()['api'];

                $router->middlewareGroup('api', $this->middlewareStack);

                $response = $router->dispatch($request);

                $router->middlewareGroup('api', $originalMiddlewareGroup);

                $this->container->instance('request', $originalRequest);

                return $response;
            });
    }
}
