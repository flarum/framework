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
use Flarum\Filter\ValidateFilterTrait;

class NumberFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'number';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $number = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.number', $negate ? '!=' : '=', $number);
    }
}
