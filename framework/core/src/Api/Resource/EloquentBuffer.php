<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Endpoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneOrMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Laravel\Field\ToMany;
use Tobyz\JsonApiServer\Laravel\Field\ToOne;
use Tobyz\JsonApiServer\Schema\Field\Relationship;

abstract class EloquentBuffer
{
    private static array $buffer = [];

    public static function add(Model $model, string $relationName, ?array $aggregate = null): void
    {
        self::$buffer[get_class($model)][$relationName][$aggregate ? $aggregate['column'].$aggregate['function'] : 'normal'][] = $model;
    }

    public static function getBuffer(Model $model, string $relationName, ?array $aggregate = null): ?array
    {
        return self::$buffer[get_class($model)][$relationName][$aggregate ? $aggregate['column'].$aggregate['function'] : 'normal'] ?? null;
    }

    public static function setBuffer(Model $model, string $relationName, ?array $aggregate, array $buffer): void
    {
        self::$buffer[get_class($model)][$relationName][$aggregate ? $aggregate['column'].$aggregate['function'] : 'normal'] = $buffer;
    }

    /**
     * @param array{name: string, relation: string, column: string, function: string, constrain: callable|null}|null $aggregate
     */
    public static function load(
        Model $model,
        string $relationName,
        ?Relationship $relationship,
        Context $context,
        ?array $aggregate = null,
    ): void {
        if (! ($models = self::getBuffer($model, $relationName, $aggregate))) {
            return;
        }

        $loader = function ($relation) use (
            $relationship,
            $context,
            $aggregate,
        ) {
            $query = $relation instanceof Relation ? $relation->getQuery() : $relation;

            // When loading the relationship, we need to scope the query
            // using the scopes defined in the related API resource – there
            // may be multiple if this is a polymorphic relationship. We
            // start by getting the resource types this relationship
            // could possibly contain.
            /** @var (AbstractDatabaseResource|AbstractResource)[] $resources */
            $resources = $context->api->resources;

            if ($relationship && $type = $relationship->collections) {
                $resources = array_intersect_key($resources, array_flip($type));
            }

            // Now, construct a map of model class names -> scoping
            // functions. This will be provided to the MorphTo::constrain
            // method in order to apply type-specific scoping.
            $constrain = [];

            if (! $aggregate && $relationship) {
                foreach ($resources as $resource) {
                    $modelClass = $resource instanceof AbstractDatabaseResource ? get_class($resource->newModel($context)) : null;

                    if ($resource instanceof AbstractDatabaseResource && ! isset($constrain[$modelClass])) {
                        $constrain[$modelClass] = function (Builder $query) use ($resource, $context, $relationship, $relation) {
                            /** @var Endpoint $endpoint */
                            $endpoint = $context->endpoint;

                            $query
                                ->with($endpoint->getEagerLoadsFor($relationship->name, $context))
                                ->with($endpoint->getWhereEagerLoadsFor($relationship->name, $context));

                            $resource->scope($query, $context);

                            if (($relationship instanceof ToMany || $relationship instanceof ToOne) && $relationship->scope) {
                                ($relationship->scope)($relation, $context);
                            }
                        };
                    }
                }
            } elseif (! empty($aggregate['constrain'])) {
                $modelClass = get_class($relation->getModel());

                $constrain[$modelClass] = fn (Builder $query) => ($aggregate['constrain'])($query, $context);
            }

            if ($relation instanceof MorphTo) {
                $relation->constrain($constrain);
            } elseif ($constrain) {
                reset($constrain)($query);
            }

            return $query;
        };

        // Filter out models that do not exist in the database (e.g. mock models
        // created by the JSON:API BelongsTo linkage shortcut for orphaned foreign
        // keys). Passing them to Laravel's load()/loadAggregate() would crash
        // when mapping query results back to the collection.
        $collection = $model->newCollection($models)
            ->filter(fn (Model $m) => $m->exists && $m->getKey() !== null);

        self::setBuffer($model, $relationName, $aggregate, []);

        if ($collection->isEmpty()) {
            return;
        }

        if (! $aggregate && $relationship) {
            if (! self::loadLimitedBelongsToMany($collection, $relationName, $loader)) {
                $collection->load([$relationName => $loader]);
            }

            // Set the inverse relation on the loaded relations.
            $collection->each(function (Model $model) use ($relationName, $relationship) {
                /** @var Model|Collection|null $related */
                $related = $model->getRelation($relationName);

                if ($related) {
                    $inverse = $relationship->inverse ?? str($model::class)->afterLast('\\')->camel()->toString();

                    $related = $related instanceof Collection ? $related : [$related];

                    foreach ($related as $rel) {
                        if ($rel->isRelation($inverse)) {
                            $rel->setRelation($inverse, $model);
                        }
                    }
                }
            });
        } else {
            self::loadAggregate($collection, $relationName, $aggregate, $loader);
        }
    }

    /**
     * Load a LIMITED BelongsToMany include (e.g. mentions' "first 4 mentioning
     * posts per post") without dragging every candidate row through the
     * database's window sort.
     *
     * Laravel compiles a limited eager load into `row_number() OVER
     * (PARTITION BY ...)` over the relation's full select — every column of
     * every candidate row (for posts: content blobs included) is materialized
     * into the window just to keep a handful per parent. Instead, run the
     * window over the KEYS alone, then fetch only the surviving rows in full.
     * The related-resource scoping (typically visibility) applies to the
     * narrow phase, so the kept ids are exactly the ones the one-phase query
     * would have kept, in the same order.
     *
     * Returns false when this isn't a limited BelongsToMany, so the caller
     * falls back to the plain eager load.
     */
    protected static function loadLimitedBelongsToMany(Collection $collection, string $relationName, callable $loader): bool
    {
        /** @var Model $parent */
        $parent = $collection->first();

        $relation = Relation::noConstraints(fn () => $parent->newModelInstance()->{$relationName}());

        if (! $relation instanceof BelongsToMany) {
            return false;
        }

        $relation->addEagerConstraints($collection->all());

        // Applies resource scoping and the relationship's scope callback —
        // including any limit — to the relation.
        $loader($relation);

        $query = $relation->getQuery();

        // In eager context, a relationship scope's ->limit() lands on the
        // base builder as groupLimit — the per-parent window — not limit.
        if (! $query->getQuery()->groupLimit && ! $query->getQuery()->limit) {
            return false;
        }

        // The eager loads belong to the full rows, not the key window.
        $eagerLoads = $query->getEagerLoads();
        $query->setEagerLoads([]);

        $related = $relation->getRelated();
        $relatedKeyName = $related->getKeyName();

        // Narrow phase: only the related key survives our select; the pivot
        // keys are appended automatically. getEager() applies the same
        // windowed limit the one-phase load would.
        $query->select($related->qualifyColumn($relatedKeyName));

        $pivotKeyName = $relation->getForeignPivotKeyName();
        $idsByParent = [];

        foreach ($relation->getEager() as $row) {
            $idsByParent[$row->getRelation('pivot')->getAttribute($pivotKeyName)][] = $row->getKey();
        }

        // Full phase: fetch the surviving rows only. Visibility was already
        // decided when the ids were selected, so no re-scoping is needed —
        // but the endpoint's nested eager loads are.
        $ids = array_unique(array_merge(...array_values($idsByParent) ?: [[]]));

        $rows = $ids
            ? $related->newQuery()->setEagerLoads($eagerLoads)->whereIn($related->getQualifiedKeyName(), $ids)->get()->keyBy($relatedKeyName)
            : collect();

        foreach ($collection as $model) {
            $models = [];

            foreach ($idsByParent[$model->getKey()] ?? [] as $id) {
                if ($rows->has($id)) {
                    $models[] = $rows[$id];
                }
            }

            $model->setRelation($relationName, $related->newCollection($models));
        }

        return true;
    }

    /**
     * Load a relation aggregate for all buffered models with ONE flat grouped
     * query.
     *
     * Laravel's loadAggregate() emits a correlated scalar subquery per model
     * (`select id, (select count(*) ... where outer.id = ...)`), which forces
     * the database to re-evaluate the aggregate's constraints — for API
     * resources, typically the full visibility scope — once per related row
     * PER MODEL, with no chance to materialize shared subqueries. A grouped
     * join over the relation's eager constraints computes every model's
     * aggregate in a single flat pass instead.
     *
     * @param array{name: string, relation: string, column: string, function: string, constrain: callable|null} $aggregate
     */
    protected static function loadAggregate(Collection $collection, string $relationName, array $aggregate, callable $loader): void
    {
        $alias = Str::snake($aggregate['name']);

        /** @var Model $parent */
        $parent = $collection->first();

        /** @var Relation $relation */
        $relation = Relation::noConstraints(fn () => $parent->newModelInstance()->{$relationName}());

        // The key the related rows are grouped and matched back to parents
        // by, exactly as eager loading would match them.
        [$groupColumn, $parentKeyName] = match (true) {
            $relation instanceof BelongsToMany => [$relation->getQualifiedForeignPivotKeyName(), $relation->getParentKeyName()],
            $relation instanceof HasOneOrMany => [$relation->getQualifiedForeignKeyName(), $relation->getLocalKeyName()],
            default => [null, null],
        };

        if ($groupColumn === null) {
            // Unsupported relation type: fall back to Laravel's per-model
            // correlated subqueries rather than guessing at key semantics.
            $collection->loadAggregate(["$relationName as $alias" => $loader], $aggregate['column'], $aggregate['function']);

            return;
        }

        $relation->addEagerConstraints($collection->all());

        // Applies the aggregate's constrain callback (e.g. visibility
        // scoping) to the relation's underlying Eloquent builder.
        $loader($relation);

        $query = $relation->getQuery();
        $grammar = $query->getQuery()->getGrammar();

        $column = $aggregate['column'] === '*'
            ? '*'
            : $grammar->wrap($relation->getRelated()->qualifyColumn($aggregate['column']));

        $results = $query
            ->toBase()
            ->select($groupColumn)
            ->selectRaw("{$aggregate['function']}({$column}) as {$grammar->wrap($alias)}")
            ->groupBy($groupColumn)
            ->get()
            ->keyBy(Str::afterLast($groupColumn, '.'));

        $default = $aggregate['function'] === 'count' ? 0 : null;

        foreach ($collection as $model) {
            $value = $results[$model->getAttribute($parentKeyName)]->{$alias} ?? $default;

            $model->setAttribute($alias, $value);
        }
    }
}
