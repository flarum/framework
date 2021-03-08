<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

/**
 * @deprecated beta 16, remove beta 17. Use AuthorFilter instead.
 */
class UserFilter extends AuthorFilter
{
    public function getFilterKey(): string
    {
        return 'user';
    }
}
