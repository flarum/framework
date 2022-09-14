<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Client
{
    /**
     * @var MiddlewarePipeInterface
     */
    protected $pipe;

    /**
     * @var User|null
     */
    protected $actor;

    /**
     * @var ServerRequestInterface|null
     */
    protected $parent;

    /**
     * @var array
     */
    protected $queryParams = [];

    /**
     * @var array
     */
    protected $body = [];

    public function __construct(MiddlewarePipeInterface $pipe)
    {
        $this->pipe = $pipe;
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

    public function withParentRequest(ServerRequestInterface $parent): Client
    {
        $new = clone $this;
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

    public function get(string $path): ResponseInterface
    {
        return $this->send('GET', $path);
    }

    public function post(string $path): ResponseInterface
    {
        return $this->send('POST', $path);
    }

    public function put(string $path): ResponseInterface
    {
        return $this->send('PUT', $path);
    }

    public function patch(string $path): ResponseInterface
    {
        return $this->send('PATCH', $path);
    }

    public function delete(string $path): ResponseInterface
    {
        return $this->send('DELETE', $path);
    }

    /**
     * Execute the given API action class, pass the input and return its response.
     *
     * @param string $method
     * @param string $path
     * @return ResponseInterface
     *
     * @internal
     */
    public function send(string $method, string $path): ResponseInterface
    {
        $request = ServerRequestFactory::fromGlobals(null, $this->queryParams, $this->body)
            ->withMethod($method)
            ->withUri(new Uri($path));

        if ($this->parent) {
            $request = $request
                ->withAttribute('ipAddress', $this->parent->getAttribute('ipAddress'))
                ->withAttribute('session', $this->parent->getAttribute('session'));
            $request = RequestUtil::withActor($request, RequestUtil::getActor($this->parent));
        }

        // This should override the actor from the parent request, if one exists.
        if ($this->actor) {
            $request = RequestUtil::withActor($request, $this->actor);
        }

        return $this->pipe->handle($request);
    }
}
