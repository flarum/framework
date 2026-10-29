<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Flarum\User\User;

/**
 * Implemented by filters that can describe themselves to the generic admin components.
 */
interface DescribesFilter
{
    /**
     * Describe the filter to the given actor.
     *
     * Return null to hide the filter, for example when `filter()` ignores the
     * value for this actor: mirror the permission check `filter()` makes.
     *
     * @return array{kind: 'string'|'boolean'|'enum'|'relationship', enum?: list<string>, related?: string}|null
     */
    public function describe(User $actor): ?array;
}
