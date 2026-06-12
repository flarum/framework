<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class UserFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'user';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = array_map(
            fn (string $username) => $this->users->getIdForUsername($username),
            $this->asStringArray($value)
        );

        $query = $state->getQuery();

        $query->whereIn(
            $query->getQuery()->raw('json_extract(payload, "$.user_id")'),
            $ids,
            'and',
            $negate
        );
    }
}
