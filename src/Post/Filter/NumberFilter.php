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

class NumberFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'number';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $number = trim($filterValue, '"');

        $filterState->getQuery()->where('posts.number', $negate ? '!=' : '=', $number);
    }
}
