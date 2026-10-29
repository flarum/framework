<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Api\Controller\ListAccessTokensController;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;

/**
 * Filters an access tokens request by the related user.
 *
 * @see ListAccessTokensController
 * @implements FilterInterface<DatabaseSearchState>
 */
class UserFilter implements FilterInterface, DescribesFilter
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'user';
    }

    /**
     * The token list is scoped to the actor's own tokens without `moderateAccessTokens`, so the
     * filter could only ever pick the actor.
     */
    public function describe(User $actor): ?array
    {
        if (! $actor->hasPermission('moderateAccessTokens')) {
            return null;
        }

        return ['kind' => 'relationship', 'related' => 'users'];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $value = $this->asInt($value);

        $state->getQuery()->where('user_id', $negate ? '!=' : '=', $value);
    }
}
