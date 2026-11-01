<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Generic;

use Flarum\Api\Endpoint\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Capability\Tool;
use Flarum\Mcp\Exception\ToolError;
use Tobyz\JsonApiServer\Exception\ResourceNotFoundException;

/**
 * A tool over one custom JSON:API endpoint, registered with
 * `Extend\Mcp::endpointTool()`. Whether it writes follows the endpoint's
 * HTTP method.
 */
class EndpointTool extends Tool
{
    public function __construct(
        protected JsonApi $api,
        protected string $type,
        protected string $endpoint,
        protected string $toolName,
        protected string $toolTitle,
        protected string $toolDescription,
    ) {
    }

    public function name(): string
    {
        return $this->toolName;
    }

    public function title(): string
    {
        return $this->toolTitle;
    }

    public function description(): string
    {
        return $this->toolDescription;
    }

    /**
     * Taken from the endpoint itself: only a GET reads. An endpoint that is
     * gone counts as a write, so it is never offered to a read-only token.
     */
    public function readOnly(): bool
    {
        return strtoupper($this->definition()->method ?? '') === 'GET';
    }

    public function inputSchema(): array
    {
        $definition = $this->definition();
        $properties = [];
        $required = [];

        if ($definition && str_contains($definition->path, '{id}')) {
            $properties['id'] = ['type' => 'string', 'description' => "The ID of the {$this->type} resource."];
            $required[] = 'id';
        }

        if ($definition && ! in_array(strtoupper($definition->method), ['GET', 'DELETE'], true)) {
            $properties['attributes'] = ['type' => 'object'];
            $properties['relationships'] = ['type' => 'object', 'description' => 'JSON:API relationship objects by name.'];
        }

        $properties['include'] = ['type' => 'array', 'items' => ['type' => 'string']];

        return array_filter([
            'type' => 'object',
            'properties' => $properties,
            'required' => $required,
        ]);
    }

    public function handle(array $arguments, McpContext $context): mixed
    {
        $definition = $this->definition();

        if (! $definition) {
            throw new ToolError("The \"{$this->type}\" resource has no \"{$this->endpoint}\" endpoint.");
        }

        $path = str_replace('{id}', rawurlencode((string) ($arguments['id'] ?? '')), $definition->path);
        $method = strtoupper($definition->method);

        $body = null;
        if (! in_array($method, ['GET', 'DELETE'], true)) {
            $data = array_filter([
                'type' => $this->type,
                'id' => isset($arguments['id']) ? (string) $arguments['id'] : null,
                'attributes' => $arguments['attributes'] ?? null,
                'relationships' => $arguments['relationships'] ?? null,
            ], fn ($value) => $value !== null && $value !== []);

            $body = ['data' => $data];
        }

        $query = empty($arguments['include']) ? [] : ['include' => implode(',', (array) $arguments['include'])];

        return $context->api->endpoint($this->type, $this->endpoint, $method, $path, $query, $body)->simplify();
    }

    protected function definition(): ?Endpoint
    {
        try {
            $resource = $this->api->getResource($this->type);
        } catch (ResourceNotFoundException) {
            return null;
        }

        if (! $resource instanceof AbstractResource) {
            return null;
        }

        foreach ($resource->resolveEndpoints() as $endpoint) {
            if ($endpoint instanceof Endpoint && $endpoint->name === $this->endpoint) {
                return $endpoint;
            }
        }

        return null;
    }
}
