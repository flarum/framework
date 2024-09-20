<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource\Concerns;

use Flarum\Api\Sort\SortColumn;

trait HasSortMap
{
    public function sortMap(): array
    {
        /** @var SortColumn[] $sorts */
        $sorts = $this->resolveSorts();

        $map = [];

        foreach ($sorts as $sort) {
            $map = array_merge($map, $sort->sortMap());
        }

        return $map;
    }
}
