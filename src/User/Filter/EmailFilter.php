<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\WrappedFilter;

class EmailFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'email';
    }

    public function filter(WrappedFilter $wrappedFilter, string $filterValue, bool $negate)
    {
        if (! $wrappedFilter->getActor()->hasPermission('user.edit')) {
            return;
        }

        $email = trim($filterValue, '"');

        $wrappedFilter->getQuery()->where('email', $negate ? '!=' : '=', $email);
    }
}
