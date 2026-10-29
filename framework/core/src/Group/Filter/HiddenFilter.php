<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Group\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class HiddenFilter implements FilterInterface, DescribesFilter
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'hidden';
    }

    public function describe(User $actor): ?array
    {
        return ['kind' => 'boolean'];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $hidden = $this->asBool($value);

        $state->getQuery()->where('is_hidden', $negate ? '!=' : '=', $hidden);
    }
}
