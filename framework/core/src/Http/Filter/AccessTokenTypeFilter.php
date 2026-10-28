<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Filter;

use Flarum\Http\AccessToken;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;

/**
 * Filters an access tokens request by the token type.
 *
 * @see \Flarum\Api\Controller\ListAccessTokensController
 * @implements FilterInterface<DatabaseSearchState>
 */
class AccessTokenTypeFilter implements FilterInterface, DescribesFilter
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'type';
    }

    /**
     * Ungated, like `filter()`. The values are the registered token types, an extension's included.
     */
    public function describe(User $actor): ?array
    {
        return ['kind' => 'enum', 'enum' => array_map('strval', array_keys(AccessToken::getModels()))];
    }

    public function filter(SearchState $state, array|string $filterValue, bool $negate): void
    {
        $type = $this->asString($filterValue);

        $state->getQuery()->where('type', $negate ? '!=' : '=', $type);
    }
}
