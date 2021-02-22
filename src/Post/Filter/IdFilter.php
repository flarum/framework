<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;

class IdFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'id';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $idString = trim($filterValue, '"');
        $ids = explode(',', $idString);

        $filterState->getQuery()->whereIn('posts.id', $ids, 'and', $negate);
    }
}
