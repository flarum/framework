<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class EmailFilter implements FilterInterface, DescribesFilter
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'email';
    }

    public function describe(User $actor): ?array
    {
        // Mirrors `filter()`, which ignores the value without this permission.
        if (! $actor->hasPermission('user.editCredentials')) {
            return null;
        }

        return ['kind' => 'string'];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        if (! $state->getActor()->hasPermission('user.editCredentials')) {
            return;
        }

        $this->constrain($state->getQuery(), $value, $negate);
    }

    protected function constrain(Builder $query, string|array $rawEmail, bool $negate): void
    {
        $email = $this->asString($rawEmail);

        $query->where('email', $negate ? '!=' : '=', $email);
    }
}
