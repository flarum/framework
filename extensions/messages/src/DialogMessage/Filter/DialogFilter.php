<?php

namespace Flarum\Messages\DialogMessage\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class DialogFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'dialog';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $state->getQuery()->where('dialog_id', $value, $negate ? '!=' : '=');
    }
}
