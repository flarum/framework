<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Filter;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;

class MentionedPostFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'mentionedPost';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $mentionedId = trim($filterValue, '"');

        $filterState
            ->getQuery()
            ->join('post_mentions_post', 'posts.id', '=', 'post_mentions_post.post_id')
            ->where('post_mentions_post.mentions_post_id', $negate ? '!=' : '=', $mentionedId);
    }
}
