<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;

class HiddenFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $hidden = $this->asBool($filterValue);

        $filterState->getQuery()->where('is_hidden', $negate ? '!=' : '=', $hidden);
    }
}
