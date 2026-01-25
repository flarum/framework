<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $usernames = $this->asStringArray($value);

        $ids = $this->users->getIdsForUsernames($usernames);

        // To be able to also use IDs.
        $actualIds = array_diff(array_map('strval', $usernames), array_map('strval', array_keys($ids)));

        if (! empty($actualIds)) {
            $ids = array_merge($ids, $actualIds);
        }

        $state->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
