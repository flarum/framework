<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;

class IdFilter implements FilterInterface
{

    public function getFilterKey(): string
    {
        return 'id';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $ids = trim($filterValue, '"');
        $ids = explode(',', $ids);

        $wrappedFilter->getQuery()->whereIn('posts.id', $ids, 'and', $negate);
    }
}
