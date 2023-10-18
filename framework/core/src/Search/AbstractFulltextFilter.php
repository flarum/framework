<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\Search\Filter\FilterInterface;

/**
 * @template TState of SearchState
 * @implements FilterInterface<TState>
 */
abstract class AbstractFulltextFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'q';
    }

    public function filter(SearchState $state, array|string $value, bool $negate): void
    {
        $this->search($state, $value);
    }

    /**
     * @param TState $state
     */
    abstract public function search(SearchState $state, string $value): void;
}
