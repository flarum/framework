<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Dialog\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Eloquent\Builder;

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
        $state->getQuery()->whereHas('users', function (Builder $query) use ($state) {
            $query
                ->where('dialog_user.user_id', $state->getActor()->id)
                ->whereColumn('dialog_user.last_read_message_id', '<', 'dialogs.last_message_id');
        });
    }
}
