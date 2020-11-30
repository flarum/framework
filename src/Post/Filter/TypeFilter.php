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

class TypeFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $type = trim($filterValue, '"');

        $wrappedFilter->getQuery()->where('posts.type', $negate ? '!=' : '=', $type);
    }
}
