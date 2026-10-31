<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

use Flarum\Api\Endpoint\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Middleware\ThrottleApi;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Http\RequestUtil;
use Flarum\Mcp\Exception\ToolError;
use Flarum\Post\Exception\FloodingException;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;

/**
 * Calls the forum's JSON:API as the MCP actor.
 *
 * Every call is a real request through `JsonApi::handle()`, so the endpoint's
 * `authenticated()` / `can()` / `admin()` gates, the model lookup and its
 * visibility scope, validation and events all run exactly as they do over
 * HTTP. `process()` is never used: it skips the gates and the lookup. Every
 * `flarum.api.throttlers` entry runs first, and throttling is never bypassed.
 */
class ApiClient
{
    /**
     * The JSON:API query parameters; any other all-lowercase name is refused.
     */
    public const array QUERY_KEYS = ['filter', 'sort', 'page', 'include', 'fields'];

    public function __construct(
        protected JsonApi $api,
        protected Container $container,
        protected User $actor,
        protected ?ServerRequestInterface $parent = null,
    ) {
    }

    /**
     * @param array<string, mixed> $query filter, sort, page, include, fields
     */
    public function index(string $type, array $query = []): ApiResult
    {
        return $this->call($type, 'index', null, $query);
    }

    /**
     * @param array<string, mixed> $query
     */
    public function show(string $type, string $id, array $query = []): ApiResult
    {
        return $this->call($type, 'show', $id, $query);
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $relationships JSON:API relationship objects, e.g. `['discussion' => ['data' => ['type' => 'discussions', 'id' => '1']]]`
     * @param array<string, mixed> $query
     */
    public function create(string $type, array $attributes, array $relationships = [], array $query = []): ApiResult
    {
        return $this->call($type, 'create', null, $query, $this->document($type, null, $attributes, $relationships));
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $relationships
     * @param array<string, mixed> $query
     */
    public function update(string $type, string $id, array $attributes, array $relationships = [], array $query = []): ApiResult
    {
        return $this->call($type, 'update', $id, $query, $this->document($type, $id, $attributes, $relationships));
    }

    public function delete(string $type, string $id): void
    {
        $this->call($type, 'delete', $id);
    }

    /**
     * Call a custom endpoint of a resource.
     *
     * @param string $path the path below the type, e.g. `/12/close`
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     */
    public function endpoint(string $type, string $endpoint, string $method, string $path, array $query = [], ?array $body = null): ApiResult
    {
        $resource = $this->resource($type);
        $definition = $this->findEndpoint($resource, $endpoint);

        return $this->send($resource, $definition, $method, '/'.$type.'/'.ltrim($path, '/'), $query, $body);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     */
    protected function call(string $type, string $endpointName, ?string $id, array $query = [], ?array $body = null): ApiResult
    {
        $resource = $this->resource($type);
        $endpoint = $this->findEndpoint($resource, $endpointName);

        $path = '/'.$type.str_replace('{id}', rawurlencode((string) $id), $endpoint->path);

        return $this->send($resource, $endpoint, $endpoint->method, $path, $query, $body);
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed>|null $body
     */
    protected function send(AbstractResource $resource, Endpoint $endpoint, string $method, string $path, array $query, ?array $body): ApiResult
    {
        // The same rule JsonApi::validateQueryParameters() applies over HTTP:
        // all-lowercase names are reserved by JSON:API, and only these five
        // exist; a name with any other character is implementation-specific.
        $reserved = array_filter(
            array_keys($query),
            fn ($key) => ! preg_match('/[^a-z]/', (string) $key) && ! in_array($key, static::QUERY_KEYS, true)
        );

        if ($reserved) {
            throw new InvalidArgumentException('Unsupported JSON:API query parameter(s): '.implode(', ', $reserved));
        }

        $routeName = $resource->type().'.'.$endpoint->name;

        if ($prefix = $resource->routeNamePrefix()) {
            $routeName = "$prefix.$routeName";
        }

        $request = (new ServerRequest([], [], rtrim($path, '/') ?: '/', strtoupper($method)))
            ->withHeader('Accept', JsonApi::MEDIA_TYPE)
            // As RouteHandlerFactory::toApiResource() does, route parameters join
            // the query: some endpoints read `id` from there rather than the path.
            ->withQueryParams([...$query, ...$this->routeParameters($endpoint->path, substr($path, strlen('/'.$resource->type())))])
            ->withAttribute('routeName', $routeName)
            ->withAttribute('bypassCsrfToken', true)
            ->withAttribute('ipAddress', $this->parent?->getAttribute('ipAddress'));

        if ($body !== null) {
            $request = $request
                ->withHeader('Content-Type', JsonApi::MEDIA_TYPE)
                ->withParsedBody($body);
        }

        $request = RequestUtil::withActor($request, $this->actor);

        if ($this->container->make(ThrottleApi::class)->throttle($request)) {
            throw new FloodingException();
        }

        $response = $this->api
            ->forResource($resource::class)
            ->forEndpoint($endpoint->name)
            ->handle($request);

        return new ApiResult($this->decode($response), $query);
    }

    /**
     * @return array<string, string>
     */
    protected function routeParameters(string $pattern, string $path): array
    {
        $patternSegments = explode('/', trim($pattern, '/'));
        $pathSegments = explode('/', trim($path, '/'));
        $parameters = [];

        foreach ($patternSegments as $index => $segment) {
            if (preg_match('/^\{(\w+)(?::[^}]*)?\}$/', $segment, $match) && isset($pathSegments[$index])) {
                $parameters[$match[1]] = rawurldecode($pathSegments[$index]);
            }
        }

        return $parameters;
    }

    /**
     * @return array<string, mixed>
     */
    protected function decode(?ResponseInterface $response): array
    {
        if (! $response) {
            return [];
        }

        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $relationships
     * @return array<string, mixed>
     */
    protected function document(string $type, ?string $id, array $attributes, array $relationships): array
    {
        $data = ['type' => $type];

        if ($id !== null) {
            $data['id'] = $id;
        }

        if ($attributes) {
            $data['attributes'] = $attributes;
        }

        if ($relationships) {
            $data['relationships'] = $relationships;
        }

        return ['data' => $data];
    }

    protected function resource(string $type): AbstractResource
    {
        try {
            $resource = $this->api->getResource($type);
        } catch (ResourceNotFoundException) {
            $resource = null;
        }

        if (! $resource instanceof AbstractResource) {
            throw new ToolError("Unknown resource type \"$type\".");
        }

        return $resource;
    }

    protected function findEndpoint(AbstractResource $resource, string $name): Endpoint
    {
        foreach ($resource->resolveEndpoints() as $endpoint) {
            if ($endpoint instanceof Endpoint && $endpoint->name === $name) {
                return $endpoint;
            }
        }

        throw new ToolError("The \"{$resource->type()}\" resource has no \"$name\" endpoint.");
    }
}
