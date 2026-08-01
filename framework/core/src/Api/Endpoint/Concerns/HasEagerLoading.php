<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Closure;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Schema\Relationship\ToMany;
use Flarum\Api\Schema\Relationship\ToOne;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
            $models->loadMissing($whereRelations);
        }

        if (! empty($simpleRelations)) {
            $models->loadMissing($simpleRelations);
        }

        $this->setInverseRelations(
            $models,
            $context,
            $resource,
            array_merge(array_keys($whereRelations), $simpleRelations)
        );
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
