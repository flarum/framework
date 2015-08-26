<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search;

trait AppliesParametersToSearch
{
    /**
     * Apply sort criteria to a discussion search.
     *
     * @param Search $search
     * @param array $sort
     */
    protected function applySort(Search $search, array $sort = null)
    {
        $sort = $sort ?: $search->getDefaultSort();

        foreach ($sort as $field => $order) {
            if (is_array($order)) {
                foreach ($order as $value) {
                    $search->getQuery()->orderByRaw(snake_case($field).' != ?', [$value]);
                }
            } else {
                $search->getQuery()->orderBy(snake_case($field), $order);
            }
        }
    }

    /**
     * @param Search $search
     * @param int $offset
     */
    protected function applyOffset(Search $search, $offset)
    {
        if ($offset > 0) {
            $search->getQuery()->skip($offset);
        }
    }

    /**
     * @param Search $search
     * @param int|null $limit
     */
    protected function applyLimit(Search $search, $limit)
    {
        if ($limit > 0) {
            $search->getQuery()->take($limit);
        }
    }
}
