<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Query;

use Flarum\Database\IntegerKey;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Flarum\User\UserRepository;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class LikedByFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected UserRepository $users
    ) {
    }

    public function getFilterKey(): string
    {
        return 'likedBy';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $likedUsername = $this->asString($value);

        $likedId = $this->users->getIdForUsername($likedUsername);

        if (! $likedId) {
            $likedId = IntegerKey::fits(new User, $likedUsername) ? (int) $likedUsername : 0;
        }

        $state
            ->getQuery()
            ->whereIn('id', function ($query) use ($likedId, $negate) {
                $query->select('post_id')
                    ->from('post_likes')
                    ->where('user_id', $negate ? '!=' : '=', $likedId);
            });
    }
}
