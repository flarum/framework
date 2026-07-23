<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Eloquent\Builder;

/**
 * Filters users by whether their email address has bounced or been marked as
 * spam. Used via the `bounced` gambit, e.g. `is:bounced`.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class BouncedFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'bounced';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        if (! $state->getActor()->hasPermission('user.editCredentials')) {
            return;
        }

        $this->constrain($state->getQuery(), $negate);
    }

    protected function constrain(Builder $query, bool $negate): void
    {
        // is:bounced   -> email_bounced_at IS NOT NULL (bounced users)
        // -is:bounced  -> email_bounced_at IS NULL     (everyone else)
        $query->whereNull('email_bounced_at', 'and', ! $negate);
    }
}
