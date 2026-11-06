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
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Tobyz\JsonApiServer\Resource\Resource;

/**
 * Builds a client-facing descriptor of a JSON:API resource for a given actor,
 * inferred from the resource's fields, endpoints, sorts and search filters.
 * Only resources opted in through `Extend\ApiResource::describable()` qualify.
 *
 * @phpstan-import-type FieldDescription from FieldDescriber
 * @phpstan-type FilterDescription array{
 *     key: string,
 *     kind: 'string'|'boolean'|'enum'|'relationship'|'date'|'number',
 *     label: string,
 *     schema?: array<string, mixed>,
 *     enum?: list<string>,
 *     enumLabels?: array<string, string>,
 *     related?: string,
 *     multiple?: bool,
 *     range?: bool,
 *     required?: bool,
 *     default?: string,
 *     negatable?: bool
 * }
 * @phpstan-type Descriptor array{
 *     schemaVersion: int,
 *     endpoint: string,
 *     label: array{one: string, other: string},
 *     capabilities: array{list: bool, create: bool, delete?: true},
 *     rowAbilities?: array<string, string>,
 *     pagination?: array{defaultLimit: int, maxLimit: int},
 *     defaultSort?: string,
 *     search?: true,
 *     sorts?: list<string>,
 *     filters?: list<FilterDescription>,
 *     fields: list<FieldDescription>
 * }
 */
class ResourceDescriber
{
    public const int SCHEMA_VERSION = 1;

    /**
     * Every top-level descriptor key, in emission order. Only `schemaVersion`,
     * `endpoint`, `label`, `capabilities` and `fields` are always present.
     */
    public const array KEYS = [
        'schemaVersion',
        'endpoint',
        'label',
        'capabilities',
        'rowAbilities',
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
     * An actor can describe what they can list or create: the same checks as
     * the descriptor's `capabilities.list` and `capabilities.create`.
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

    /**
     * @return Descriptor
     */
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
            'label' => $this->label($resource->type()),
            'capabilities' => [
                'list' => $this->canList($resource, $request, $index),
                'create' => $canCreate,
            ],
        ];

        if ($this->canDelete($request, $endpoints['delete'] ?? null, $contexts['update'])) {
            $descriptor['capabilities']['delete'] = true;
        }

        if ($rowAbilities = $this->rowAbilities($resource, $fields)) {
            $descriptor['rowAbilities'] = $rowAbilities;
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

        if ($filters = $this->filters($resource->type(), $search['filters'], $fields)) {
            $descriptor['filters'] = $filters;
        }

        $descriptor['fields'] = $fields;

        return $descriptor;
    }

    /**
     * @param array{create: DescribeContext, update: DescribeContext, show: DescribeContext, index: DescribeContext} $contexts
     * @param array<string, true> $sortable
     * @param list<'create'|'update'> $writeModes
     * @return list<FieldDescription>
     */
    protected function describeFields(AbstractDatabaseResource $resource, array $contexts, array $sortable, array $writeModes): array
    {
        $fields = [];

        foreach ($resource->resolveFields() as $field) {
            try {
                $description = $this->fields->describe($field, $resource, $contexts, $sortable, $writeModes);
            } catch (Throwable $e) {
                // A field that cannot be described at all is left out rather than failing the whole descriptor.
                $this->fields->report($e, $field, $resource->type());

                continue;
            }

            if ($description !== null) {
                $fields[] = $description;
            }
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
     * The type's name, singular and plural: `core.admin.resource.{type}.label_one`
     * and `label_other` when they exist, otherwise the humanised type.
     *
     * @return array{one: string, other: string}
     */
    protected function label(string $type): array
    {
        $humanised = ucfirst(str_replace(['-', '_'], ' ', $type));

        return [
            'one' => $this->fields->translate("core.admin.resource.$type.label_one") ?? Str::singular($humanised),
            'other' => $this->fields->translate("core.admin.resource.$type.label_other") ?? $humanised,
        ];
    }

    /**
     * The attributes that carry each row's edit and delete permission: `canEdit`
     * and `canDelete` when the actor can see them, overridden through
     * `Extend\ApiResource::rowAbilities()` (a null removes the action).
     *
     * @param list<FieldDescription> $fields
     * @return array<string, string>
     */
    protected function rowAbilities(AbstractDatabaseResource $resource, array $fields): array
    {
        $visible = [];

        foreach ($fields as $field) {
            if (($field['visible'] ?? true) && ! isset($field['relationship'])) {
                $visible[$field['name']] = true;
            }
        }

        $abilities = array_filter(
            ['edit' => 'canEdit', 'delete' => 'canDelete'],
            fn (string $attribute) => isset($visible[$attribute])
        );

        $overrides = $this->container->make('flarum.api.resource_row_abilities');

        foreach ($overrides as $class => $override) {
            if ($resource instanceof $class) {
                $abilities = array_merge($abilities, $override);
            }
        }

        // An overridden attribute the actor cannot see would hide the action on every row.
        return array_filter($abilities, fn ($attribute) => is_string($attribute) && isset($visible[$attribute]));
    }

    /**
     * Completes each filter's description with a translated `label` (the filter's
     * own, else the same-named field's, else `core.admin.resource.{type}.filters.{snake_key}`,
     * else the humanised key) and `enumLabels` for its enum values.
     *
     * @param array<string, array<string, mixed>> $descriptions keyed by filter key, already scoped to the actor
     * @param list<FieldDescription> $fields
     * @return list<FilterDescription>
     */
    protected function filters(string $type, array $descriptions, array $fields = []): array
    {
        $fieldsByName = array_column($fields, null, 'name');
        $described = [];

        foreach ($descriptions as $key => $description) {
            $key = (string) $key;
            $field = $fieldsByName[$key] ?? null;
            $snake = Str::snake($key);

            $label = $description['label'] ?? null;
            $description['label'] = is_string($label) && $label !== ''
                ? ($this->fields->translate($label) ?? $label)
                : ($field['label']
                    ?? $this->fields->translate("core.admin.resource.$type.filters.$snake")
                    ?? ucfirst(str_replace('_', ' ', $snake)));

            $enum = array_map('strval', $description['enum'] ?? []);

            if ($enum) {
                $labels = [];

                foreach ((array) ($description['enumLabels'] ?? []) as $value => $enumLabel) {
                    if (is_string($enumLabel)) {
                        $labels[(string) $value] = $this->fields->translate($enumLabel) ?? $enumLabel;
                    }
                }

                $labels += $this->fields->enumLabels($type, $key, $enum, 'filters.')
                    + array_intersect_key($field['enumLabels'] ?? [], array_flip($enum));

                if ($labels) {
                    $description['enumLabels'] = $labels;
                } else {
                    unset($description['enumLabels']);
                }
            }

            $described[] = ['key' => $key] + $description;
        }

        return $described;
    }
}
