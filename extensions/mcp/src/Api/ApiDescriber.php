<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Api;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\JsonApi;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Api\Sort\SortColumn;
use Flarum\Http\RequestUtil;
use Flarum\Mcp\Capability\CapabilityRegistry;
use Flarum\Mcp\Overview\ExtensionOwnership;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchManager;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\ServerRequest;
use stdClass;
use Throwable;
use Tobyz\JsonApiServer\Resource\Listable;
use Tobyz\JsonApiServer\Schema\Field\Attribute;
use Tobyz\JsonApiServer\Schema\Field\Field;
use Tobyz\JsonApiServer\Schema\Field\Relationship;
use Tobyz\JsonApiServer\Schema\Field\ToMany;

/**
 * Describes JSON:API types for `describe_api`: fields with their JSON Schema,
 * filters from the type's searcher, and sorts.
 */
class ApiDescriber
{
    public function __construct(
        protected Container $container,
        protected JsonApi $api,
        protected CapabilityRegistry $registry,
        protected ExtensionOwnership $ownership,
    ) {
    }

    /**
     * @param array<string, Endpoint\Endpoint> $operations
     * @return array<string, mixed>
     */
    public function summary(AbstractResource $resource, array $operations, User $actor): array
    {
        $attributes = [];
        $relationships = [];

        foreach ($this->visibleFields($resource, $actor) as $field) {
            if ($field instanceof Relationship) {
                $relationships[] = $field->name;
            } else {
                $attributes[] = $field->name;
            }
        }

        return array_filter([
            'type' => $resource->type(),
            'extension' => $this->ownership->owner($resource),
            'description' => $this->registry->description($resource->type()),
            'operations' => array_keys($operations),
            'attributes' => $attributes,
            'relationships' => $relationships,
            'filters' => isset($operations['index']) ? $this->filters($resource) : [],
            'sorts' => isset($operations['index']) ? $this->sorts($resource, $actor) : [],
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @param array<string, Endpoint\Endpoint> $operations
     * @return array<string, mixed>
     */
    public function describe(AbstractResource $resource, array $operations, User $actor): array
    {
        $create = $this->writeContext($resource, $operations['create'] ?? null, $actor);
        $update = $this->writeContext($resource, $operations['update'] ?? null, $actor);

        $attributes = [];
        $relationships = [];

        foreach ($this->visibleFields($resource, $actor) as $field) {
            if ($field instanceof Relationship) {
                $relationships[] = array_filter([
                    'name' => $field->name,
                    'types' => array_values($field->collections ?? []),
                    'kind' => $field instanceof ToMany ? 'to-many' : 'to-one',
                    'includable' => $field->includable,
                    'description' => $field->description,
                    'writable' => $this->writable($field, $create, $update, $this->registry->isRelationshipWriteDenied($resource->type(), $field->name)),
                ], fn ($value) => $value !== null);
            } else {
                $schema = $field instanceof Attribute && $field->type ? $field->type->schema() : [];
                $schema = array_filter([...$schema, ...$field->getSchema()], fn ($value) => $value !== null);
                unset($schema['description'], $schema['nullable']);

                $attributes[] = array_filter([
                    'name' => $field->name,
                    'schema' => $schema ?: null,
                    'description' => $field->description,
                    'nullable' => $field->nullable,
                    'writable' => $this->writable($field, $create, $update, $this->registry->isAttributeWriteDenied($resource->type(), $field->name)),
                ], fn ($value) => $value !== null);
            }
        }

        $description = [
            ...$this->summary($resource, $operations, $actor),
            'attributes' => $attributes,
            'relationships' => $relationships,
        ];

        $index = $operations['index'] ?? null;

        if ($index instanceof Endpoint\Index) {
            $description['defaultSort'] = $index->defaultSort;
            $description['pageLimit'] = ['default' => $index->limit, 'max' => $index->maxLimit];
        }

        return $description;
    }

    /**
     * The fields the actor can see.
     *
     * Visibility is evaluated for the actor without a real model: against a
     * blank one (a new, unsaved instance, or an empty object for a resource
     * with no database model). So a field shows when its visibility is
     * unconditional or depends only on the actor (`isAdmin()`, a global
     * permission); a rule about a particular model (its author, whether it is
     * hidden) does not pass on a blank model, or throws, and hides the field.
     * That errs towards not advertising a field the actor may never see.
     *
     * @return list<Field>
     */
    protected function visibleFields(AbstractResource $resource, User $actor): array
    {
        $context = $this->actorContext($resource, $actor);
        $fields = [];

        foreach ($resource->resolveFields() as $field) {
            if (! $field instanceof Field) {
                continue;
            }

            try {
                $visible = $field->isVisible($context->withField($field));
            } catch (Throwable) {
                $visible = false;
            }

            if ($visible) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    protected function actorContext(AbstractResource $resource, User $actor): Context
    {
        $context = (new Context($this->api, RequestUtil::withActor(new ServerRequest(), $actor)))
            ->withCollection($resource)
            ->withResource($resource);

        return $context->withModel($resource instanceof AbstractDatabaseResource ? $resource->newModel($context) : new stdClass());
    }

    /**
     * Filter keys for an index: those of the type's searcher, plus `q` when
     * its search driver does full text.
     *
     * @return list<string>
     */
    public function filters(AbstractResource $resource): array
    {
        if (! $resource instanceof AbstractDatabaseResource) {
            return $resource instanceof Listable
                ? array_values(array_map(fn ($filter) => $filter->name, $resource->filters()))
                : [];
        }

        /** @var SearchManager $search */
        $search = $this->container->make(SearchManager::class);
        $model = $resource->model();

        if (! $search->searchable($model)) {
            return [];
        }

        $default = $search->driver(DatabaseSearchDriver::name());
        $searcher = $default->getSearchers()[$model] ?? null;
        $keys = [];

        foreach ((array) ($this->container->make('flarum.search.filters')[$searcher] ?? []) as $filterClass) {
            try {
                $filter = $this->container->make($filterClass);

                if ($filter instanceof FilterInterface) {
                    $keys[] = $filter->getFilterKey();
                }
            } catch (Throwable) {
                // A filter that cannot be built is not one the model can use.
            }
        }

        $driver = $search->driverFor($model);
        $fulltext = $driver::name() === DatabaseSearchDriver::name()
            ? isset($this->container->make('flarum.search.fulltext')[$searcher])
            : $driver->supports($model);

        if ($fulltext) {
            array_unshift($keys, 'q');
        }

        return array_values(array_unique($keys));
    }

    /**
     * Sort names, `-` for descending, plus the aliases a sort defines.
     *
     * @return list<string>
     */
    public function sorts(AbstractResource $resource, User $actor): array
    {
        $context = $this->actorContext($resource, $actor)->withModel(null);
        $sorts = [];

        foreach ($resource->resolveSorts() as $sort) {
            try {
                if (! $sort->isVisible($context)) {
                    continue;
                }
            } catch (Throwable) {
                continue;
            }

            $sorts[] = $sort->name;
            $sorts[] = '-'.$sort->name;

            if ($sort instanceof SortColumn) {
                foreach (array_keys($sort->sortMap()) as $alias) {
                    $sorts[] = (string) $alias;
                }
            }
        }

        return array_values(array_unique($sorts));
    }

    protected function writeContext(AbstractResource $resource, ?Endpoint\Endpoint $endpoint, User $actor): ?Context
    {
        if (! $endpoint) {
            return null;
        }

        $request = RequestUtil::withActor(new ServerRequest(), $actor);

        $context = (new Context($this->api, $request))
            ->withCollection($resource)
            ->withResource($resource)
            ->withEndpoint($endpoint);

        if ($resource instanceof AbstractDatabaseResource) {
            $context = $context->withModel($resource->newModel($context));
        }

        return $context;
    }

    /**
     * Best effort for this actor: a rule that needs a real model to decide
     * is reported as writable, and the field's own gate decides on the call.
     * A field the generic tools refuse to write is never writable.
     *
     * @return array{create: bool, update: bool}|null
     */
    protected function writable(Field $field, ?Context $create, ?Context $update, bool $denied = false): ?array
    {
        if (! $field->writable || (! $create && ! $update)) {
            return null;
        }

        if ($denied) {
            return ['create' => false, 'update' => false];
        }

        $check = function (?Context $context) use ($field): bool {
            if (! $context) {
                return false;
            }

            try {
                return $field->isWritable($context->withField($field));
            } catch (Throwable) {
                return true;
            }
        };

        return ['create' => $check($create), 'update' => $check($update)];
    }
}
