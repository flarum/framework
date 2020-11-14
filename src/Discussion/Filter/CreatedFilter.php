<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;

class CreatedFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'created';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        $pattern = '(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';

        preg_match('/^'.$pattern.'$/i', $filterValue, $matches);

        // If we've just been provided with a single YYYY-MM-DD date, then find
        // discussions that were started on that exact date. But if we've been
        // provided with a YYYY-MM-DD..YYYY-MM-DD range, then find discussions
        // that were started during that period.
        if (empty($matches[2])) {
            $wrappedFilter->getQuery()->whereDate('created_at', $negate ? '!=' : '=', $matches[1]);
        } else {
            $wrappedFilter->getQuery()->whereBetween('created_at', [$matches[1], $matches[3]], 'and', $negate);
        }
    }
}
