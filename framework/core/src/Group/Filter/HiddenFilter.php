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

class HiddenFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $filterState->getQuery()->where('is_hidden', $negate ? '!=' : '=', $filterValue);
    }
}
