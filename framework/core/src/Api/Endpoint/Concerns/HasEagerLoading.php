<?php

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Api\Context;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;
use Tobyz\JsonApiServer\Laravel\EloquentResource;

/**
 * This is directed at eager loading relationships apart from the request includes.
 */
trait HasEagerLoading
{
    /**
     * @var string[]
     */
    protected array $loadRelations = [];

    /**
     * @var array<string, callable>
     */
    protected array $loadRelationCallables = [];

    /**
     * Eager loads relationships needed for serializer logic.
     *
     * First level relationships will be loaded regardless of whether they are included in the response.
     * Sub-level relationships will only be loaded if the upper level was included or manually loaded.
     *
     * @example If a relationship such as: 'relation.subRelation' is specified,
     * it will only be loaded if 'relation' is or has been loaded.
     * To force load the relationship, both levels have to be specified,
     * example: ['relation', 'relation.subRelation'].
     *
     * @param string|string[] $relations
     */
    public function eagerLoad(array|string $relations): self
    {
        $this->loadRelations = array_merge($this->loadRelations, array_map('strval', (array) $relations));

        return $this;
    }

    /**
     * Allows loading a relationship with additional query modification.
     *
     * @param string $relation: Relationship name, see load method description.
     * @template R of Relation
     * @param (callable(Builder|R, \Psr\Http\Message\ServerRequestInterface|null, array): void) $callback
     *
     * The callback to modify the query, should accept:
     * - \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Relations\Relation $query: A query object.
     * - \Psr\Http\Message\ServerRequestInterface|null $request: An instance of the request.
     * - array $relations: An array of relations that are to be loaded.
     */
    public function eagerLoadWhere(string $relation, callable $callback): self
    {
        $this->loadRelationCallables = array_merge($this->loadRelationCallables, [$relation => $callback]);

        return $this;
    }

    /**
     * Eager loads the required relationships.
     */
    protected function loadRelations(Collection $models, Context $context, array $included = []): void
    {
        if (! $context->collection instanceof EloquentResource) {
            return;
        }

        $request = $context->request;

        $included = $this->stringInclude($included);
        $models = $models->filter(fn ($model) => $model instanceof Model);

        $addedRelations = $this->loadRelations;
        $addedRelationCallables = $this->loadRelationCallables;

        $relations = $included;

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

    /**
     * From format of: 'relation' => [ ...nested ] to ['relation', 'relation.nested']
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
