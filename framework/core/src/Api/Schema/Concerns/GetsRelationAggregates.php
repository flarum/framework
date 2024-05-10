<?php

namespace Flarum\Api\Schema\Concerns;

use Closure;
use Tobyz\JsonApiServer\Schema\Type\Number;

trait GetsRelationAggregates
{
    /**
     * @var array{relation: string, column: string, function: string, constrain: Closure}|null
     */
    public ?array $relationAggregate = null;

    public function relationAggregate(string $relation, string $column, string $function, ?Closure $constrain = null): static
    {
        if (! $this->type instanceof Number) {
            throw new \InvalidArgumentException('Relation aggregates can only be used with number attributes');
        }

        $this->relationAggregate = compact('relation', 'column', 'function', 'constrain');

        return $this;
    }

    public function countRelation(string $relation, ?Closure $constrain = null): static
    {
        return $this->relationAggregate($relation, '*', 'count', $constrain);
    }

    public function sumRelation(string $relation, string $column, ?Closure $constrain = null): static
    {
        return $this->relationAggregate($relation, $column, 'sum', $constrain);
    }

    public function avgRelation(string $relation, string $column, ?Closure $constrain = null): static
    {
        return $this->relationAggregate($relation, $column, 'avg', $constrain);
    }

    public function minRelation(string $relation, string $column, ?Closure $constrain = null): static
    {
        return $this->relationAggregate($relation, $column, 'min', $constrain);
    }

    public function maxRelation(string $relation, string $column, ?Closure $constrain = null): static
    {
        return $this->relationAggregate($relation, $column, 'max', $constrain);
    }

    public function getRelationAggregate(): ?array
    {
        return $this->relationAggregate;
    }
}
