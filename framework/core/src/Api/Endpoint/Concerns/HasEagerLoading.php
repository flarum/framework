<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Closure;
use Flarum\Api\Context as FlarumContext;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema\Relationship\ToMany;
use Flarum\Api\Schema\Relationship\ToOne;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Context;

/**
 * This is directed at eager loading relationships apart from the request includes.
 */
trait HasEagerLoading
{
    /**
     * @var array<string|callable>
     */
    protected array $loadRelations = [];

    /**
     * @var array<string, callable>
     */
    protected array $loadRelationWhere = [];

    /**
     * Eager loads relationships needed for serializer logic.
     *
     * @param string|string[] $relations
     */
    public function eagerLoad(array|string|Closure $relations): static
    {
        if (! is_callable($relations)) {
            $this->loadRelations = array_merge($this->loadRelations, array_map('strval', (array) $relations));
        } else {
            $this->loadRelations[] = $relations;
        }

        return $this;
    }

    /**
     * Eager load relations when a relation is included in the serialized response.
     *
     * @param array<string, array<string>> $includedToRelations An array of included relation to relations to load 'includedRelation' => ['relation1', 'relation2']
     */
    public function eagerLoadWhenIncluded(array $includedToRelations): static
    {
        return $this->eagerLoad(function (array $included) use ($includedToRelations) {
            $relations = [];

            foreach ($includedToRelations as $includedRelation => $includedRelations) {
                if (in_array($includedRelation, $included)) {
                    $relations = array_merge($relations, $includedRelations);
                }
            }

            return $relations;
        });
    }

    /**
     * Allows loading a relationship with additional query modification.
     *
     * @param string $relation: Relationship name, see load method description.
     * @param callable $callback
     *
     * The callback to modify the query, should accept:
     * - \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query: A query object.
     * - Context $context: An instance of the API context.
     * - array $relations: An array of relations that are to be loaded.
     */
    public function eagerLoadWhere(string $relation, callable $callback): static
    {
        $this->loadRelationWhere = array_merge($this->loadRelationWhere, [$relation => $callback]);

        return $this;
    }

    /**
     * Eager loads relationships before serialization.
     */
    protected function loadRelations(Collection $models, Context $context, array $included = []): void
    {
        $resource = $context->collection;

        if (! $resource instanceof AbstractDatabaseResource) {
            return;
        }

        $included = $this->stringInclude($included);

        $relations = $this->compileSimpleEagerLoads($context, $included);
        $addedRelationWhere = $this->compileWhereEagerLoads($context);

        foreach ($addedRelationWhere as $name => $callable) {
            $relations[] = $name;
        }

        if (! empty($relations)) {
            $relations = array_unique($relations);
        }

        $whereRelations = [];
        $simpleRelations = [];

        foreach ($relations as $relation) {
            if (isset($addedRelationWhere[$relation])) {
                $whereRelations[$relation] = $addedRelationWhere[$relation];
            } else {
                $simpleRelations[] = $relation;
            }
        }

        if (! empty($whereRelations)) {
            $models->loadMissing($this->scopeEagerLoads(
                array_keys($whereRelations),
                $context,
                $resource,
                $whereRelations
            ));
        }

        if (! empty($simpleRelations)) {
            $models->loadMissing($this->scopeEagerLoads($simpleRelations, $context, $resource));
        }

        $this->setInverseRelations(
            $models,
            $context,
            $resource,
            array_merge(array_keys($whereRelations), $simpleRelations)
        );
    }

    /**
     * Apply each related resource's scope() to the relations pre-loaded here.
     *
     * Relations loaded lazily at serialisation time are scoped by
     * EloquentBuffer::load(), which is the only place a related resource's
     * scope() (i.e. whereVisibleTo) is applied to a relationship query. A
     * relation pre-loaded here never reaches that code — the serialiser skips
     * the buffer for anything already loaded — so without scoping it here, a
     * relation named in an endpoint's eagerLoad() list is serialised with no
     * visibility check: the discussion Show endpoint's `firstPost.user.groups`
     * handed a moderator-hidden opening post to anyone who could see the
     * discussion.
     *
     * Note that loadMissing() binds a path's callback to its LAST segment only,
     * so 'firstPost.user.groups' => $scope would scope `groups` and leave
     * `firstPost` unscoped. Each path is expanded into its prefixes, each
     * scoped by the resource that owns it.
     *
     * @param string[] $relations
     * @return array<string, callable>
     */
    private function scopeEagerLoads(array $relations, Context $context, AbstractDatabaseResource $resource, array $callbacks = []): array
    {
        // Resource scopes are declared against Flarum's context. Without one
        // there is nothing to apply, so fall back to what each route would
        // have loaded: its own callbacks where it has them, bare names
        // otherwise.
        if (! $context instanceof FlarumContext) {
            return $callbacks ?: $relations;
        }

        $scoped = [];

        foreach ($relations as $relation) {
            $owner = $resource;
            $path = '';

            foreach (explode('.', $relation) as $segment) {
                $path = $path === '' ? $segment : "$path.$segment";

                $field = $owner ? $this->relationshipField($owner, $segment, $context) : null;
                $related = $field ? $this->singleResourceFor($field, $context) : null;

                // A segment we cannot resolve to a single database resource has
                // no scope to apply. Keep loading it — dropping the path would
                // reintroduce the N+1 this eager load exists to prevent — and
                // leave it to the serialiser, which still authorises each field.
                // loadMissing() hands the callback the Relation, while scope()
                // takes the Eloquent builder underneath it.
                // A relation registered through eagerLoadWhere() carries its own
                // query modification; it is applied alongside the scope, not
                // instead of it.
                $callback = $callbacks[$path] ?? null;

                // Polymorphic relations span several resources, so the scope has
                // to be applied per concrete model class through constrain() --
                // the same shape EloquentBuffer::load() uses. Calling scope() on
                // a MorphTo's own builder would resolve the related model's
                // scopes against the wrong model.
                $constrain = $field ? $this->morphConstraints($field, $context) : [];

                $scoped[$path] = function (Relation|Builder $query) use ($related, $context, $callback, $constrain) {
                    if ($query instanceof MorphTo && $constrain) {
                        $query->constrain($constrain);
                    } elseif ($related) {
                        $related->scope($query instanceof Relation ? $query->getQuery() : $query, $context);
                    }

                    if ($callback) {
                        $callback($query, $context);
                    }
                };

                $owner = $related;
            }
        }

        return $scoped;
    }

    /**
     * The relationship field a relation path segment refers to, if any.
     */
    private function relationshipField(AbstractDatabaseResource $resource, string $segment, Context $context): ToOne|ToMany|null
    {
        $field = collect($context->fields($resource))
            ->first(fn ($field) => ($field instanceof ToOne || $field instanceof ToMany)
                && (($field->property ?? $field->name) === $segment || $field->name === $segment));

        return $field instanceof ToOne || $field instanceof ToMany ? $field : null;
    }

    /**
     * The database resource behind a relationship field, when it resolves to
     * exactly one. Polymorphic relations return null and are scoped through
     * morphConstraints() instead.
     */
    private function singleResourceFor(ToOne|ToMany $field, Context $context): ?AbstractDatabaseResource
    {
        $types = (array) ($field->collections ?? []);

        if (count($types) !== 1) {
            return null;
        }

        $related = $context->api->resources[$types[0]] ?? null;

        return $related instanceof AbstractDatabaseResource ? $related : null;
    }

    /**
     * A model class => scoping closure map for a polymorphic relationship, for
     * MorphTo::constrain().
     *
     * @return array<class-string, callable>
     */
    private function morphConstraints(ToOne|ToMany $field, FlarumContext $context): array
    {
        $constrain = [];

        foreach ((array) ($field->collections ?? []) as $type) {
            $resource = $context->api->resources[$type] ?? null;

            if (! $resource instanceof AbstractDatabaseResource) {
                continue;
            }

            $modelClass = get_class($resource->newModel($context));

            if (! isset($constrain[$modelClass])) {
                $constrain[$modelClass] = fn (Builder $query) => $resource->scope($query, $context);
            }
        }

        return $constrain;
    }

    /**
     * Point relations loaded above back at the models they were loaded for.
     *
     * The relationship buffer does this for the relations it loads (see
     * EloquentBuffer::load()), but relations pre-loaded here arrive through
     * loadMissing(), which wires nothing back — and the buffer then skips
     * them because they are already loaded. Serializing such a related model
     * re-fetched its parent one row at a time: every visibility check on an
     * included firstPost read $post->discussion, which IS the discussion
     * being listed.
     *
     * @param string[] $relations
     */
    private function setInverseRelations(Collection $models, Context $context, AbstractDatabaseResource $resource, array $relations): void
    {
        if ($models->isEmpty() || empty($relations)) {
            return;
        }

        /** @var array<string, ToOne|ToMany> $fields */
        $fields = array_filter(
            $context->fields($resource),
            fn ($field) => $field instanceof ToOne || $field instanceof ToMany
        );

        // Only the first segment of each loaded path is a relation of the
        // models at hand; deeper segments belong to other parents.
        $segments = array_unique(array_map(
            fn (string $relation) => explode('.', $relation)[0],
            $relations
        ));

        foreach ($segments as $segment) {
            // A relationship field may expose the relation under a different
            // name than the Eloquent relation used in eager load paths.
            $field = collect($fields)->first(
                fn (ToOne|ToMany $field) => ($field->property ?? $field->name) === $segment || $field->name === $segment
            );

            foreach ($models as $model) {
                if (! $model->relationLoaded($segment)) {
                    continue;
                }

                $related = $model->getRelation($segment);

                if (! $related) {
                    continue;
                }

                $inverse = $field->inverse ?? Str::camel(class_basename($model));

                foreach ($related instanceof Collection ? $related : [$related] as $rel) {
                    if ($rel instanceof Model && $rel->isRelation($inverse)) {
                        $rel->setRelation($inverse, $model);
                    }
                }
            }
        }
    }

    protected function compileSimpleEagerLoads(Context $context, array $included): array
    {
        $relations = [];

        foreach ($this->loadRelations as $relation) {
            if (is_callable($relation)) {
                $returnedRelations = $relation($included, $context);
                $relations = array_merge($relations, array_map('strval', (array) $returnedRelations));
            } else {
                $relations[] = $relation;
            }
        }

        return $relations;
    }

    protected function compileWhereEagerLoads(Context $context): array
    {
        $relations = array_map(
            callback: fn ($callable) => fn ($query) => $callable($query, $context),
            array: $this->loadRelationWhere
        );

        return $relations;
    }

    public function getEagerLoadsFor(string $included, Context $context): array
    {
        $subRelations = [];

        $includes = $this->stringInclude($this->getInclude($context));

        foreach ($this->compileSimpleEagerLoads($context, $includes) as $relation) {
            if (! is_callable($relation)) {
                if (Str::startsWith($relation, "$included.")) {
                    $subRelations[] = Str::after($relation, "$included.");
                }
            } else {
                $returnedRelations = $relation($includes, $context);
                $subRelations = array_merge($subRelations, array_map('strval', (array) $returnedRelations));
            }
        }

        return $subRelations;
    }

    public function getWhereEagerLoadsFor(string $included, Context $context): array
    {
        $subRelations = [];

        foreach ($this->loadRelationWhere as $relation => $callable) {
            if (Str::startsWith($relation, "$included.")) {
                $subRelations[$relation] = Str::after($relation, "$included.");
            }
        }

        return $subRelations;
    }

    /**
     * From format of: 'relation' => [ ...nested ] to ['relation', 'relation.nested'].
     */
    private function stringInclude(array $include): array
    {
        $relations = [];

        foreach ($include as $relation => $nested) {
            $relations[] = $relation;

            if (is_array($nested)) {
                foreach ($this->stringInclude($nested) as $nestedRelation) {
                    $relations[] = $relation.'.'.$nestedRelation;
                }
            }
        }

        return $relations;
    }
}
