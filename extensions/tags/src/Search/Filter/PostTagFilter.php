<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class PostTagFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'tag';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $ids = $this->asIntArray($value);

        $state->getQuery()
            ->join('discussion_tag', 'discussion_tag.discussion_id', '=', 'posts.discussion_id')
            ->whereIn('discussion_tag.tag_id', $ids, 'and', $negate);
    }
}
