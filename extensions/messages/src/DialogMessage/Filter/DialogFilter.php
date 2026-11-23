<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\DialogMessage\Filter;

use Flarum\Messages\Dialog;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class DialogFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'dialog';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = $this->asKeyArray($value, new Dialog);

        $state->getQuery()->{$negate ? 'whereNotIn' : 'whereIn'}('dialog_id', $ids);
    }
}
