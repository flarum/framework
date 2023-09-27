<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Filter;

use Flarum\Search\SearchState;

/**
 * @template TState of SearchState
 */
interface FilterInterface
{
    /**
     * This filter will only be run when a query contains a filter param with this key.
     */
    public function getFilterKey(): string;

    /**
     * Filters a query.
     *
     * @param TState $state
     */
    public function filter(SearchState $state, string|array $value, bool $negate): void;
}
