<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

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

    abstract public function search(SearchState $state, string $query): void;
}
