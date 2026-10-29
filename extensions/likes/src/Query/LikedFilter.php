<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Query;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\DescribesFilter;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class LikedFilter implements FilterInterface, DescribesFilter
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'liked';
    }

    /**
     * The value is a post id: the filter keeps the users who liked that post.
     * Ungated, like `filter()`.
     */
    public function describe(User $actor): ?array
    {
        return ['kind' => 'relationship', 'related' => 'posts'];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $likedId = $this->asString($value);

        $state
            ->getQuery()
            ->whereIn('id', function ($query) use ($likedId) {
                $query->select('user_id')
                    ->from('post_likes')
                    ->where('post_id', $likedId);
            }, 'and', $negate);
    }
}
