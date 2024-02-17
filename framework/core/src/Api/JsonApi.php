<?php

namespace Flarum\Api;

use Flarum\Api\Endpoint\Endpoint;
use Flarum\Api\Endpoint\EndpointRoute;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Uri;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\JsonApi as BaseJsonApi;
use Tobyz\JsonApiServer\Resource\Collection;
use Tobyz\JsonApiServer\Resource\Resource;

class JsonApi extends BaseJsonApi
{
    protected string $resourceClass;
    protected string $endpoint;
    protected ?Request $baseRequest = null;
    protected ?Container $container = null;

    public function forResource(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function forEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    protected function makeContext(Request $request): Context
    {
        if (! $this->endpoint || ! $this->resourceClass || ! class_exists($this->resourceClass)) {
            throw new BadRequestException('No resource or endpoint specified');
        }

        $collection = $this->getCollection($this->resourceClass);

        return (new Context($this, $request))
            ->withCollection($collection)
            ->withEndpoint($this->findEndpoint($collection));
    }

    protected function findEndpoint(?Collection $collection): Endpoint
    {
        /** @var \Flarum\Api\Endpoint\Endpoint $endpoint */
        foreach ($collection->endpoints() as $endpoint) {
            if ($endpoint::class === $this->endpoint) {
                return $endpoint;
            }
        }

        throw new BadRequestException('Invalid endpoint specified');
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

    public function execute(array $body, array $internal = [], array $options = []): mixed
    {
        /** @var EndpointRoute $route */
        $route = (new $this->endpoint)->route();

        $request = $this->baseRequest ?? ServerRequestFactory::fromGlobals();

        if (! empty($options['actor'])) {
            $request = RequestUtil::withActor($request, $options['actor']);
        }

        $resource = $this->getCollection($this->resourceClass);

        $request = $request
            ->withMethod($route->method)
            ->withUri(new Uri($route->path))
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

        return $context->endpoint->execute($context);
    }

    public function validateQueryParameters(Request $request): void
    {
        foreach ($request->getQueryParams() as $key => $value) {
            if (
                !preg_match('/[^a-z]/', $key) &&
                !in_array($key, ['include', 'fields', 'filter', 'page', 'sort'])
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
