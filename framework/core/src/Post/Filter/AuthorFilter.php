<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Filter\ValidateFilterTrait;
use Flarum\User\UserRepository;

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

    public function filter(FilterState $filterState, string|array $filterValue, bool $negate): void
    {
        $usernames = $this->asStringArray($filterValue);

        $ids = $this->users->query()->whereIn('username', $usernames)->pluck('id');

        $filterState->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
