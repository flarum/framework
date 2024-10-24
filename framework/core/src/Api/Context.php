<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api;

use Flarum\Http\RequestUtil;
use Flarum\Search\SearchResults;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;
use Tobyz\JsonApiServer\Context as BaseContext;
use Tobyz\JsonApiServer\Resource\Resource;
use Tobyz\JsonApiServer\Schema\Field\Field;
use WeakMap;

class Context extends BaseContext
{
    private WeakMap $fields;

    public int|string|null $modelId = null;
    public ?array $requestIncludes = null;
    protected ?SearchResults $search = null;

    /**
     * Data passed internally when reusing resource endpoint logic.
     */
    protected array $internal = [];

    /**
     * Parameters mutated on the current instance.
     * Useful for passing information between different field callbacks.
     */
    protected array $parameters = [];

    public function __construct(\Tobyz\JsonApiServer\JsonApi $api, ServerRequestInterface $request)
    {
        $this->fields = new WeakMap();

        parent::__construct($api, $request);
    }

    /**
     * Get the fields for the given resource, keyed by name.
     *
     * @return array<string, Field>
     */
    public function fields(Resource $resource): array
    {
        if (isset($this->fields[$resource])) {
            return $this->fields[$resource];
        }

        $fields = [];

        // @phpstan-ignore-next-line
        foreach ($resource->resolveFields() as $field) {
            $fields[$field->name] = $field;
        }

        return $this->fields[$resource] = $fields;
    }

    public function withSearchResults(SearchResults $search): static
    {
        $new = clone $this;
        $new->search = $search;

        return $new;
    }

    public function withInternal(string $key, mixed $value): static
    {
        $new = clone $this;
        $new->internal[$key] = $value;

        return $new;
    }

    public function getSearchResults(): ?SearchResults
    {
        return $this->search;
    }

    public function internal(string $key, mixed $default = null): mixed
    {
        return $this->internal[$key] ?? $default;
    }

    public function getActor(): User
    {
        return RequestUtil::getActor($this->request);
    }

    public function setParam(string $key, mixed $default = null): static
    {
        $this->parameters[$key] = $default;

        return $this;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    public function creating(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Create && (! $resource || is_a($this->collection, $resource));
    }

    public function updating(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Update && (! $resource || is_a($this->collection, $resource));
    }

    public function deleting(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Delete && (! $resource || is_a($this->collection, $resource));
    }

    public function showing(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Show && (! $resource || is_a($this->collection, $resource));
    }

    public function listing(string|null $resource = null): bool
    {
        return $this->endpoint instanceof Endpoint\Index && (! $resource || is_a($this->collection, $resource));
    }

    public function withRequest(ServerRequestInterface $request): static
    {
        $new = parent::withRequest($request);
        $new->requestIncludes = null;

        return $new;
    }

    public function withModelId(int|string|null $id): static
    {
        $new = clone $this;
        $new->modelId = $id;

        return $new;
    }

    public function withRequestIncludes(array $requestIncludes): static
    {
        $new = clone $this;
        $new->requestIncludes = $requestIncludes;

        return $new;
    }

    public function extractIdFromPath(BaseContext $context): ?string
    {
        /** @var Endpoint\Endpoint $endpoint */
        $endpoint = $context->endpoint;

        $currentPath = trim($context->path(), '/');
        $path = trim($context->collection->name().$endpoint->path, '/');

        if (! str_contains($path, '{id}')) {
            return null;
        }

        $segments = explode('/', $path);
        $idSegmentIndex = array_search('{id}', $segments);
        $currentPathSegments = explode('/', $currentPath);

        return $currentPathSegments[$idSegmentIndex] ?? null;
    }
}
