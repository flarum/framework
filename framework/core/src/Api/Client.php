<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Flarum\Http\Router;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function get(string $path): Response
    {
        return $this->send('GET', $path);
    }

    public function post(string $path): Response
    {
        return $this->send('POST', $path);
    }

    public function put(string $path): Response
    {
        return $this->send('PUT', $path);
    }

    public function patch(string $path): Response
    {
        return $this->send('PATCH', $path);
    }

    public function delete(string $path): Response
    {
        return $this->send('DELETE', $path);
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @internal
     */
    public function send(string $method, string $path): Response
    {
        $parent = $this->parent ?: Request::createFromGlobals();

        $symfonyRequest = SymfonyRequest::create(
            $path, $method, $this->queryParams, $parent->cookies->all(), $parent->files->all(), $parent->server->all(), $this->body
        );

        $request = Request::createFromBase($symfonyRequest);

        if ($this->parent) {
            $request->attributes->set('ipAddress', $this->parent->attributes->get('ipAddress'));
            $request->attributes->set('session', $this->parent->attributes->get('session'));
            $request = RequestUtil::withActor($request, RequestUtil::getActor($this->parent));
        }

        // This should override the actor from the parent request, if one exists.
        if ($this->actor) {
            $request = RequestUtil::withActor($request, $this->actor);
        }

        return (new Pipeline($this->container))
            ->send($request)
            ->through($this->middlewareStack)
            ->then(function (Request $request) {
                return $this->container->make(Router::class)->dispatch($request);
            });
    }
}
