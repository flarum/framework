<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Api\Controller\ListAccessTokensController;
use Flarum\Http\AccessToken;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Filters an access tokens request by the related user.
 *
 * @see ListAccessTokensController
 * @implements FilterInterface<DatabaseSearchState>
 */
class UserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'user';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = $this->asKey($value, new AccessToken, 'user_id');

        $state->getQuery()->whereIn('user_id', $ids, 'and', $negate);
    }
}
