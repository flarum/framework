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

class TypeFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $type = $this->asString($filterValue);

        $filterState->getQuery()->where('posts.type', $negate ? '!=' : '=', $type);
    }
}
