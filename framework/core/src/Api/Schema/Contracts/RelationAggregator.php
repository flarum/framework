<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Contracts;

use Closure;

interface RelationAggregator
{
    public function relationAggregate(string $relation, string $column, string $function): static;

    /**
     * @return array{relation: string, column: string, function: string, constrain: Closure|null}|null
     */
    public function getRelationAggregate(): ?array;
}
