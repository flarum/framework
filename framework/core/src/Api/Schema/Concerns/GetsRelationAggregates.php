<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Concerns;

use Closure;
use Tobyz\JsonApiServer\Schema\Type\Number;

trait GetsRelationAggregates
{
    /**
     * @var array{name: string, relation: string, column: string, function: string, constrain: Closure}|null
     */
    public ?array $relationAggregate = null;

    public function relationAggregate(string $relation, string $column, string $function, ?Closure $constrain = null): static
    {
        if (! $this->type instanceof Number) {
            throw new \InvalidArgumentException('Relation aggregates can only be used with number attributes');
        }

        $name = $this->name;

        $this->relationAggregate = compact('name', 'relation', 'column', 'function', 'constrain');

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
