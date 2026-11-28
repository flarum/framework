<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

use Flarum\Api\JsonApi;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CallEndpoint implements RequestHandlerInterface
{
    public function __construct(
        protected JsonApi $api,
        protected string $resource,
        protected string $endpoint,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // As RouteHandlerFactory::toApiResource() does, after the middleware:
        // some endpoints read `id` from the query rather than the path.
        $request = $request->withQueryParams([...$request->getQueryParams(), ...$request->getAttribute('routeParameters', [])]);

        return $this->api
            ->forResource($this->resource)
            ->forEndpoint($this->endpoint)
            ->handle($request);
    }
}
