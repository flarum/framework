<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Query;

use Flarum\Post\Post;
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
     * Ungated, like `filter()`, which only counts posts visible to the actor.
     */
    public function describe(User $actor): ?array
    {
        return ['kind' => 'relationship', 'related' => 'posts', 'negatable' => true];
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $likedId = $this->asString($value);
        $actor = $state->getActor();

        // A post the actor cannot see behaves exactly like a nonexistent one
        // (no likers; `-liked` keeps everyone), so neither its likers nor its
        // existence show.
        $state
            ->getQuery()
            ->whereIn('id', function ($query) use ($likedId, $actor) {
                $query->select('user_id')
                    ->from('post_likes')
                    ->where('post_id', $likedId)
                    ->whereIn('post_id', Post::whereVisibleTo($actor)->where('posts.id', $likedId)->select('posts.id'));
            }, 'and', $negate);
    }
}
