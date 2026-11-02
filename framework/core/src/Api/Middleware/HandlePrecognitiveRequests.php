<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Middleware;

use Flarum\Api\Endpoint\Create;
use Flarum\Api\Endpoint\Update;
use Flarum\Api\Exception\PrecognitionNotSupportedException;
use Flarum\Api\JsonApi;
use Flarum\Api\Precognition;
use Flarum\Api\Resource\AbstractResource;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Throwable;

/**
 * A `Precognition` request to anything but a resource's create or update
 * endpoint, or with a value other than `true`, fails with a 400: running it
 * normally would perform the write the client only meant to validate.
 */
class HandlePrecognitiveRequests implements Middleware
{
    public function __construct(
        protected Container $container
    ) {
    }

    public function process(Request $request, Handler $handler): Response
    {
        if (! Precognition::requested($request)) {
            return $handler->handle($request);
        }

        if (Precognition::wellFormed($request) && $this->supportsPrecognition($request)) {
            $request = $request->withAttribute(Precognition::ATTRIBUTE, true);
        } else {
            $handler = new class implements Handler {
                public function handle(Request $request): Response
                {
                    throw new PrecognitionNotSupportedException();
                }
            };
        }

        // Render errors here so error responses carry the precognition headers too.
        $response = $this->container->make('flarum.api.error_handler')->process($request, $handler);

        return $response
            ->withHeader(Precognition::HEADER, 'true')
            ->withAddedHeader('Vary', Precognition::HEADER);
    }

    protected function supportsPrecognition(Request $request): bool
    {
        if (! in_array(strtoupper($request->getMethod()), ['POST', 'PATCH'], true)) {
            return false;
        }

        $routeName = $request->getAttribute('routeName');

        if (! is_string($routeName) || ! str_contains($routeName, '.')) {
            return false;
        }

        // Resource routes are named `[prefix.]{type}.{endpoint}`.
        $segments = explode('.', $routeName);
        $endpointName = array_pop($segments);
        $type = array_pop($segments);

        try {
            $resource = $this->container->make(JsonApi::class)->getResource($type);
        } catch (Throwable) {
            return false;
        }

        if (! $resource instanceof AbstractResource) {
            return false;
        }

        $expectedName = implode('.', array_filter([$resource->routeNamePrefix(), $resource->type(), $endpointName]));

        if ($expectedName !== $routeName) {
            return false;
        }

        foreach ($resource->resolveEndpoints() as $endpoint) {
            if ($endpoint->name === $endpointName) {
                return $endpoint instanceof Create || $endpoint instanceof Update;
            }
        }

        return false;
    }
}
