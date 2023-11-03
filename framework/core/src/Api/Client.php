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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Client
{
    protected ?User $actor = null;
    protected ?ServerRequestInterface $parent = null;
    protected array $queryParams = [];
    protected array $body = [];
    protected bool $errorHandling = true;

    public function __construct(
        protected ClientMiddlewarePipe $pipe
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

    public function withoutErrorHandling(): Client
    {
        $new = clone $this;
        $new->errorHandling = false;

        return $new;
    }

    public function withErrorHandling(): Client
    {
        $new = clone $this;
        $new->errorHandling = true;

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

        return $this->pipe
            ->errorHandling($this->errorHandling)
            ->handle($request);
    }
}
