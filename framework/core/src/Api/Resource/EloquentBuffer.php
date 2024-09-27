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

        $collection = $model->newCollection($models);

        if (! $aggregate && $relationship) {
            $collection->load([$relationName => $loader]);

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
            $alias = Str::snake($aggregate['name']);
            $collection->loadAggregate(["$relationName as $alias" => $loader], $aggregate['column'], $aggregate['function']);
        }

        self::setBuffer($model, $relationName, $aggregate, []);
    }
}
