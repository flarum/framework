<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Query;

use Illuminate\Support\Str;

/**
 * @internal
 */
trait ApplyQueryParametersTrait
{
    /**
     * Apply sort criteria to a discussion query.
     *
     * @param AbstractQueryState $query
     * @param array $sort
     * @param bool $sortIsDefault
     */
    protected function applySort(AbstractQueryState $query, array $sort = null, bool $sortIsDefault = false)
    {
        if ($sortIsDefault && ! empty($query->getDefaultSort())) {
            $sort = $query->getDefaultSort();
        }

        if (is_callable($sort)) {
            $sort($query->getQuery());
        } else {
            foreach ((array) $sort as $field => $order) {
                if (is_array($order)) {
                    foreach ($order as $value) {
                        $query->getQuery()->orderByRaw(Str::snake($field).' != ?', [$value]);
                    }
                } else {
                    $query->getQuery()->orderBy(Str::snake($field), $order);
                }
            }
        }
    }

    /**
     * @param AbstractQueryState $query
     * @param int $offset
     */
    protected function applyOffset(AbstractQueryState $query, $offset)
    {
        if ($offset > 0) {
            $query->getQuery()->skip($offset);
        }
    }

    /**
     * @param AbstractQueryState $query
     * @param int|null $limit
     */
    protected function applyLimit(AbstractQueryState $query, $limit)
    {
        if ($limit > 0) {
            $query->getQuery()->take($limit);
        }
    }
}
