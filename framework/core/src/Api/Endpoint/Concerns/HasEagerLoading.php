<?php

namespace Flarum\Api\Endpoint\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This is directed at eager loading relationships apart from the request includes.
 */
trait HasEagerLoading
{
    /**
     * @var string[][]
     */
    protected static array $loadRelations = [];

    /**
     * @var array<string, callable>
     */
    protected static array $loadRelationCallables = [];

    /**
     * Default relations to eager load.
     */
    protected array $eagerLoads = [];

    public function eagerLoad(string ...$relations): static
    {
        $this->eagerLoads = array_merge($this->eagerLoads, $relations);

        return $this;
    }

    /**
     * Returns the relations to load added by extenders.
     *
     * @return string[]
     */
    protected function getRelationsToLoad(Collection $models): array
    {
        $addedRelations = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelations[$class])) {
                $addedRelations = array_merge($addedRelations, static::$loadRelations[$class]);
            }
        }

        return $addedRelations;
    }

    /**
     * Returns the relation callables to load added by extenders.
     *
     * @return array<string, callable>
     */
    protected function getRelationCallablesToLoad(Collection $models): array
    {
        $addedRelationCallables = [];

        foreach (array_reverse(array_merge([static::class], class_parents($this))) as $class) {
            if (isset(static::$loadRelationCallables[$class])) {
                $addedRelationCallables = array_merge($addedRelationCallables, static::$loadRelationCallables[$class]);
            }
        }

        return $addedRelationCallables;
    }

    /**
     * Eager loads the required relationships.
     */
    protected function loadRelations(Collection $models, ServerRequestInterface $request = null): void
    {
        return; // @todo ditch for getValue defer?
        $addedRelations = $this->getRelationsToLoad($models);
        $addedRelationCallables = $this->getRelationCallablesToLoad($models);

        $relations = $this->eagerLoads;

        foreach ($addedRelationCallables as $name => $relation) {
            $addedRelations[] = $name;
        }

        if (! empty($addedRelations)) {
            usort($addedRelations, function ($a, $b) {
                return substr_count($a, '.') - substr_count($b, '.');
            });

            foreach ($addedRelations as $relation) {
                if (str_contains($relation, '.')) {
                    $parentRelation = Str::beforeLast($relation, '.');

                    if (! in_array($parentRelation, $relations, true)) {
                        continue;
                    }
                }

                $relations[] = $relation;
            }
        }

        if (! empty($relations)) {
            $relations = array_unique($relations);
        }

        $callableRelations = [];
        $nonCallableRelations = [];

        foreach ($relations as $relation) {
            if (isset($addedRelationCallables[$relation])) {
                $load = $addedRelationCallables[$relation];

                $callableRelations[$relation] = function ($query) use ($load, $request, $relations) {
                    $load($query, $request, $relations);
                };
            } else {
                $nonCallableRelations[] = $relation;
            }
        }

        if (! empty($callableRelations)) {
            $models->loadMissing($callableRelations);
        }

        if (! empty($nonCallableRelations)) {
            $models->loadMissing($nonCallableRelations);
        }
    }
}
