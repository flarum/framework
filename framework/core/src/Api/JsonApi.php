<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Api\Endpoint\Endpoint;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use RuntimeException;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;
use Tobyz\JsonApiServer\JsonApi as BaseJsonApi;
use Tobyz\JsonApiServer\Resource\Collection;
use Tobyz\JsonApiServer\Resource\Resource;

class JsonApi extends BaseJsonApi
{
    protected string $resourceClass;
    protected string $endpointName;
    protected ?Request $baseRequest = null;
    protected ?Container $container = null;

    public function forResource(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function forEndpoint(string $endpointName): self
    {
        $this->endpointName = $endpointName;

        return $this;
    }

    protected function makeContext(Request $request): Context
    {
        if (! $this->endpointName || ! $this->resourceClass || ! class_exists($this->resourceClass)) {
            throw new BadRequestException('No resource or endpoint specified');
        }

        $collection = $this->getCollection($this->resourceClass);

        return (new Context($this, $request))
            ->withCollection($collection)
            ->withEndpoint($this->findEndpoint($collection));
    }

    protected function findEndpoint(?Collection $collection): Endpoint
    {
        if (! $collection instanceof AbstractResource) {
            throw new RuntimeException('Resource '.$collection::class.' must extend '.AbstractResource::class);
        }

        /** @var Endpoint $endpoint */
        foreach ($collection->resolveEndpoints() as $endpoint) {
            if ($endpoint->name === $this->endpointName) {
                return $endpoint;
            }
        }

        throw new BadRequestException('Invalid endpoint specified');
    }

    /**
     * Get a collection by name or class.
     *
     * @throws ResourceNotFoundException if the collection has not been defined.
     */
    public function getCollection(string $type): Collection
    {
        if (isset($this->collections[$type])) {
            return $this->collections[$type];
        }

        foreach ($this->collections as $instance) {
            if ($instance instanceof $type) {
                return $instance;
            }
        }

        throw new ResourceNotFoundException($type);
    }

    /**
     * Get a resource by type or class.
     *
     * @throws ResourceNotFoundException if the resource has not been defined.
     */
    public function getResource(string $type): Resource
    {
        if (isset($this->resources[$type])) {
            return $this->resources[$type];
        }

        foreach ($this->resources as $instance) {
            if ($instance instanceof $type) {
                return $instance;
            }
        }

        throw new ResourceNotFoundException($type);
    }

    public function withRequest(Request $request): self
    {
        $this->baseRequest = $request;

        return $this;
    }

    public function handle(Request $request): Response
    {
        $context = $this->makeContext($request);

        return $context->endpoint->handle($context);
    }

    public function process(array $body, array $internal = [], array $options = []): mixed
    {
        $request = $this->baseRequest ?? ServerRequestFactory::fromGlobals();

        if (! empty($options['actor'])) {
            $request = RequestUtil::withActor($request, $options['actor']);
        }

        $resource = $this->getCollection($this->resourceClass);

        $request = $request
            ->withParsedBody([
                ...$body,
                'data' => [
                    ...($request->getParsedBody()['data'] ?? []),
                    ...($body['data'] ?? []),
                    'type' => $resource instanceof Resource
                        ? $resource->type()
                        : $resource->name(),
                ],
            ]);

        $context = $this->makeContext($request)
            ->withModelId($body['data']['id'] ?? null);

        foreach ($internal as $key => $value) {
            $context = $context->withInternal($key, $value);
        }

        $endpoint = $context->endpoint;

        if (! $endpoint instanceof Endpoint) {
            throw new RuntimeException('The endpoint '.$endpoint::class.' must extend '.Endpoint::class);
        }

        $context = $context->withRequest(
            $request
                ->withMethod($endpoint->method)
                ->withUri(new Uri($endpoint->path))
        );

        return $endpoint->process($context);
    }

    public function validateQueryParameters(Request $request): void
    {
        foreach ($request->getQueryParams() as $key => $value) {
            if (
                ! preg_match('/[^a-z]/', $key) &&
                ! in_array($key, ['include', 'fields', 'filter', 'page', 'sort'])
            ) {
                throw (new BadRequestException("Invalid query parameter: $key"))->setSource([
                    'parameter' => $key,
                ]);
            }
        }
    }

    public function typeForModel(string $modelClass): ?string
    {
        foreach ($this->resources as $resource) {
            if ($resource instanceof AbstractDatabaseResource && $resource->model() === $modelClass) {
                return $resource->type();
            }
        }

        return null;
    }

    public function typesForModels(array $modelClasses): array
    {
        return array_values(array_unique(array_map(fn ($modelClass) => $this->typeForModel($modelClass), $modelClasses)));
    }

    public function container(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }
}
