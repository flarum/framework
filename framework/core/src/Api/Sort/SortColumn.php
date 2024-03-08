<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Sort;

use Tobyz\JsonApiServer\Laravel\Sort\SortColumn as BaseSortColumn;

class SortColumn extends BaseSortColumn
{
    protected array $alias = [
        'asc' => null,
        'desc' => null,
    ];

    public function ascendingAlias(?string $alias): static
    {
        $this->alias['asc'] = $alias;

        return $this;
    }

    public function descendingAlias(?string $alias): static
    {
        $this->alias['desc'] = $alias;

        return $this;
    }

    public function sortMap(): array
    {
        $map = [];

        foreach ($this->alias as $direction => $alias) {
            if ($alias) {
                $sort = ($direction === 'asc' ? '' : '-').$this->name;
                $map[$alias] = $sort;
            }
        }

        return $map;
    }
}
