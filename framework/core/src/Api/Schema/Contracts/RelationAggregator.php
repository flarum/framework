<?php

namespace Flarum\Api\Schema\Contracts;

interface RelationAggregator
{
    public function relationAggregate(string $relation, string $column, string $function): static;

    /**
     * @return array{relation: string, column: string, function: string}|null
     */
    public function getRelationAggregate(): ?array;
}
