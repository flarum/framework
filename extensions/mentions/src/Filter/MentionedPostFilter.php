<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class MentionedPostFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'mentionedPost';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $mentionedId = trim($value, '"');

        $state
            ->getQuery()
            ->join('post_mentions_post', 'posts.id', '=', 'post_mentions_post.post_id')
            ->where('post_mentions_post.mentions_post_id', $negate ? '!=' : '=', $mentionedId);
    }
}
