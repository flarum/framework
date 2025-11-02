<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Filters an access tokens request by the token type.
 *
 * @see \Flarum\Api\Controller\ListAccessTokensController
 * @implements FilterInterface<DatabaseSearchState>
 */
class AccessTokenTypeFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    public function filter(SearchState $state, array|string $filterValue, bool $negate): void
    {
        $type = $this->asString($filterValue);

        $state->getQuery()->where('type', $negate ? '!=' : '=', $type);
    }
}
