<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Illuminate\Support\Str;

trait ApplySearchParametersTrait
{
    /**
     * Apply sort criteria to a discussion search.
     *
     * @param AbstractSearch $search
     * @param array $sort
     */
    protected function applySort(AbstractSearch $search, array $sort = null)
    {
        $sort = $sort ?: $search->getDefaultSort();

        if (is_callable($sort)) {
            $sort($search->getQuery());
        } else {
            foreach ($sort as $field => $order) {
                if (is_array($order)) {
                    foreach ($order as $value) {
                        $search->getQuery()->orderByRaw(Str::snake($field).' != ?', [$value]);
                    }
                } else {
                    $search->getQuery()->orderBy(Str::snake($field), $order);
                }
            }
        }
    }

    /**
     * @param AbstractSearch $search
     * @param int $offset
     */
    protected function applyOffset(AbstractSearch $search, $offset)
    {
        if ($offset > 0) {
            $search->getQuery()->skip($offset);
        }
    }

    /**
     * @param AbstractSearch $search
     * @param int|null $limit
     */
    protected function applyLimit(AbstractSearch $search, $limit)
    {
        if ($limit > 0) {
            $search->getQuery()->take($limit);
        }
    }
}
