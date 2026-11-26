<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Filter;

use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class MentionedPostFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'mentionedPost';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = $this->asKey($value, new Post);

        $state
            ->getQuery()
            ->join('post_mentions_post', 'posts.id', '=', 'post_mentions_post.post_id')
            ->whereIn('post_mentions_post.mentions_post_id', $ids, 'and', $negate);
    }
}
