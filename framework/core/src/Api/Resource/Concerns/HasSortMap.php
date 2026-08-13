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
        $sorts = $this->resolveSorts();

        $map = [];

        foreach ($sorts as $sort) {
            // A resource's sorts are not necessarily core's SortColumn — an
            // extension may register its own Sort subclass (extension-manager
            // does, to proxy a sort to an external registry). Only core's
            // SortColumn carries the alias-to-API-sort map; anything else has
            // no map to contribute and is skipped rather than fatalling the
            // whole admin payload.
            if ($sort instanceof SortColumn) {
                $map = array_merge($map, $sort->sortMap());
            }
        }

        return $map;
    }
}
