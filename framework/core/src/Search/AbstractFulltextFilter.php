<?php

namespace Flarum\Search;

use Flarum\Search\FilterInterface;

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
