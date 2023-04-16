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

class DiscussionFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'discussion';
    }

    public function filter(FilterState $filterState, $filterValue, bool $negate)
    {
        $discussionId = $this->asInt($filterValue);

        $filterState->getQuery()->where('posts.discussion_id', $negate ? '!=' : '=', $discussionId);
    }
}
