<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Http\RequestUtil;
use Flarum\Search\SearchManager;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Tobyz\JsonApiServer\Resource\Resource;

/**
 * Builds a client-facing descriptor of a JSON:API resource for a given actor.
 *
 * The descriptor is inferred from the resource's own fields, endpoints, sorts
 * and search filters, evaluated against synthetic `DescribeContext`s.
 *
 * Only resources opted in through `Extend\ApiResource::describable()` are
 * describable, so any extension can opt in any resource.
 */
class ResourceDescriber
{
    /**
     * Always emitted: the one exception to default-omission.
     */
    public const int SCHEMA_VERSION = 1;

    /**
     * Every top-level descriptor key, in emission order. Only `schemaVersion`,
     * `endpoint`, `capabilities` and `fields` are always present.
     */
    public const array KEYS = [
        'schemaVersion',
        'endpoint',
        'capabilities',
        'pagination',
        'defaultSort',
        'search',
        'sorts',
        'filters',
        'fields',
    ];

    public function __construct(
        protected JsonApi $api,
        protected SearchManager $search,
        protected FieldDescriber $fields,
        protected Container $container
    ) {
    }

    /**
     * @phpstan-assert-if-true AbstractDatabaseResource $resource
     */
    public function isDescribable(Resource $resource): bool
    {
        if (! $resource instanceof AbstractDatabaseResource) {
            return false;
        }

        foreach ($this->container->make('flarum.api.describable_resources') as $class) {
            if ($resource instanceof $class) {
                return true;
            }
        }

        return false;
    }

    /**
     * "You can describe what you can list or create" (spec D2): the same
     * checks as the descriptor's `capabilities.list` and `capabilities.create`.
     */
    public function canDescribe(Resource $resource, User $actor, ServerRequestInterface $request): bool
    {
        if (! $this->isDescribable($resource)) {
            return false;
        }

        $request = $this->standInRequest($request, $actor);
        $endpoints = $this->endpoints($resource);

        return $this->canList($resource, $request, $endpoints['index'] ?? null)
            || $this->canCreate($resource, $request, $endpoints['create'] ?? null);
    }

    public function describe(Resource $resource, User $actor, ServerRequestInterface $request): array
    {
        if (! $this->isDescribable($resource)) {
            throw new InvalidArgumentException("The resource [{$resource->type()}] is not describable.");
        }

        $request = $this->standInRequest($request, $actor);

        $endpoints = $this->endpoints($resource);

        $contexts = $this->contexts($resource, $request, $endpoints);

        $sorts = $this->visibleSorts($resource, $contexts['index']);
        $sortable = array_fill_keys($sorts, true);

        $canCreate = $this->canCreate($resource, $request, $endpoints['create'] ?? null);

        // A field's create-time writability means nothing to an actor who
        // cannot create: `UserResource::email` is writable whenever
        // `creating()`, which would describe it to every user searcher.
        $writeModes = $canCreate ? ['create', 'update'] : ['update'];

        $fields = $this->describeFields($resource, $contexts, $sortable, $writeModes);

        $index = $endpoints['index'] ?? null;

        $descriptor = [
            'schemaVersion' => self::SCHEMA_VERSION,
            'endpoint' => '/'.$resource->type(),
            'capabilities' => [
                'list' => $this->canList($resource, $request, $index),
                'create' => $canCreate,
            ],
        ];

        if ($this->canDelete($request, $endpoints['delete'] ?? null, $contexts['update'])) {
            $descriptor['capabilities']['delete'] = true;
        }

        if ($index instanceof Endpoint\Index) {
            if ($index->limit !== null) {
                $descriptor['pagination'] = ['defaultLimit' => $index->limit, 'maxLimit' => $index->maxLimit];
            }

            if ($index->defaultSort !== null) {
                $descriptor['defaultSort'] = $index->defaultSort;
            }
        }

        $search = $this->search->describeSearch($resource->model(), $actor);

        if ($search['fulltext']) {
            $descriptor['search'] = true;
        }

        if ($sorts) {
            $descriptor['sorts'] = $sorts;
        }

        if ($filters = $this->filters($search['filters'])) {
            $descriptor['filters'] = $filters;
        }

        $descriptor['fields'] = $fields;

        return $descriptor;
    }

    /**
     * Describes every field, in two passes.
     *
     * A `required_without` / `required_without_all` rule counts as required only
     * when no alternative it names is offered to the client (spec D3, N5), and
     * whether a field is offered is known only once every field is described.
     *
     * @param array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext} $contexts
     * @param array<string, true> $sortable
     * @param list<'create'|'update'> $writeModes
     * @return list<array<string, mixed>>
     */
    protected function describeFields(AbstractDatabaseResource $resource, array $contexts, array $sortable, array $writeModes): array
    {
        $described = [];

        foreach ($resource->resolveFields() as $field) {
            $description = $this->fields->describe($field, $resource, $contexts, $sortable, $writeModes);

            if ($description !== null) {
                $described[] = [$field, $description];
            }
        }

        // A field is offered when it is described and not a hidden widget.
        $offered = [];

        foreach ($described as [, $description]) {
            if (($description['ui']['widget'] ?? null) !== 'hidden') {
                $offered[$description['name']] = true;
            }
        }

        $fields = [];

        foreach ($described as [$field, $description]) {
            if ($this->fields->hasRequiredWithout($field)) {
                $description = $this->fields->describe($field, $resource, $contexts, $sortable, $writeModes, $offered) ?? $description;
            }

            $fields[] = $description;
        }

        return $fields;
    }

    /**
     * @return array{index?: Endpoint\Index, create?: Endpoint\Create, update?: Endpoint\Update, show?: Endpoint\Show, delete?: Endpoint\Delete}
     */
    protected function endpoints(AbstractDatabaseResource $resource): array
    {
        $found = [];

        foreach ($resource->resolveEndpoints() as $endpoint) {
            $key = match (true) {
                $endpoint instanceof Endpoint\Index => 'index',
                $endpoint instanceof Endpoint\Create => 'create',
                $endpoint instanceof Endpoint\Update => 'update',
                $endpoint instanceof Endpoint\Show => 'show',
                $endpoint instanceof Endpoint\Delete => 'delete',
                default => null,
            };

            if ($key && ! isset($found[$key])) {
                $found[$key] = $endpoint;
            }
        }

        return $found;
    }

    /**
     * @return array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext}
     */
    protected function contexts(AbstractDatabaseResource $resource, ServerRequestInterface $request, array $endpoints): array
    {
        $base = $this->baseContext($resource, $request);

        // `newModel()` may have side effects for guests (UserResource sets the
        // locale preference from a cookie), so a guest gets a bare instance.
        $isGuest = RequestUtil::getActor($request)->isGuest();

        $contexts = [];

        foreach (['create', 'update', 'show', 'index'] as $mode) {
            $context = $base->withMode($mode);

            if (isset($endpoints[$mode])) {
                $context = $context->withEndpoint($endpoints[$mode]);
            }

            if ($mode !== 'index') {
                $model = $isGuest ? new ($resource->model()) : $resource->newModel($context);

                if ($mode !== 'create') {
                    $model->exists = true;
                }

                $context = $context->withModel($model);
            }

            $contexts[$mode] = $context;
        }

        return $contexts;
    }

    /**
     * The request closures see: the actor, but none of the describe request's own
     * query parameters, body or route parameters, which belong to the describe
     * endpoint rather than to the resource being described.
     */
    protected function standInRequest(ServerRequestInterface $request, User $actor): ServerRequestInterface
    {
        $request = $request
            ->withQueryParams([])
            ->withParsedBody(null)
            ->withoutAttribute('routeParameters');

        return RequestUtil::withActor($request, $actor);
    }

    protected function baseContext(AbstractDatabaseResource $resource, ServerRequestInterface $request): DescribeContext
    {
        return (new DescribeContext($this->api, $request))
            ->withCollection($resource)
            ->withResource($resource);
    }

    /**
     * Type-level: the Index endpoint against an index-mode context without a model.
     */
    protected function canList(AbstractDatabaseResource $resource, ServerRequestInterface $request, ?Endpoint\Index $endpoint): bool
    {
        if (! $endpoint) {
            return false;
        }

        return $this->allowed($endpoint, $this->baseContext($resource, $request)->withMode('index')->withEndpoint($endpoint));
    }

    /**
     * Type-level: the Create endpoint is checked without a model, so only global
     * permissions apply. Field-level create checks keep their synthetic model.
     */
    protected function canCreate(AbstractDatabaseResource $resource, ServerRequestInterface $request, ?Endpoint\Create $endpoint): bool
    {
        if (! $endpoint) {
            return false;
        }

        return $this->allowed($endpoint, $this->baseContext($resource, $request)->withMode('create')->withEndpoint($endpoint));
    }

    /**
     * Type-level: the Delete endpoint's own authorization against the blank
     * stored model of the update context, so model policies apply (users need
     * `user.delete`). A policy that decides per record (e.g. "own posts only")
     * sees no owner and may deny; whether a given row can be deleted is still
     * the row's `canDelete`.
     */
    protected function canDelete(ServerRequestInterface $request, ?Endpoint\Delete $endpoint, DescribeContext $update): bool
    {
        if (! $endpoint || RequestUtil::getActor($request)->isGuest()) {
            return false;
        }

        return $this->allowed($endpoint, $update->withEndpoint($endpoint));
    }

    protected function allowed(?object $endpoint, Context $context): bool
    {
        if (! $endpoint || ! method_exists($endpoint, 'isVisible')) {
            return false;
        }

        try {
            return (bool) $endpoint->isVisible($context);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @return list<string>
     */
    protected function visibleSorts(AbstractDatabaseResource $resource, Context $context): array
    {
        $names = [];

        foreach ($resource->resolveSorts() as $sort) {
            try {
                if (! method_exists($sort, 'isVisible') || $sort->isVisible($context)) {
                    $names[] = $sort->name;
                }
            } catch (Throwable) {
                // Fail closed.
            }
        }

        return $names;
    }

    /**
     * @param array<string, array<string, mixed>> $descriptions keyed by filter key, already scoped to the actor
     * @return list<array<string, mixed>>
     */
    protected function filters(array $descriptions): array
    {
        $described = [];

        foreach ($descriptions as $key => $description) {
            $described[] = ['key' => $key] + $description;
        }

        return $described;
    }
}
