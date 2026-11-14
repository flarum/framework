<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Dialog\Filter;

use Flarum\Messages\Dialog;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class UnreadFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'unread';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $query = Dialog::query()->whereVisibleTo($state->getActor())->unread($state->getActor())->select('dialogs.id');
        $state->getQuery()->{$negate ? 'whereNotIn' : 'whereIn'}('dialogs.id', $query);
    }
}
