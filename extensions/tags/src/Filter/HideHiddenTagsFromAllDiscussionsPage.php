<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Filter;

use Flarum\Query\QueryCriteria;
use Flarum\Search\SearchState;
use Flarum\Tags\Tag;

class HideHiddenTagsFromAllDiscussionsPage
{
    public function __invoke(SearchState $state, QueryCriteria $queryCriteria): void
    {
        if (count($state->getActiveFilters()) > 0 || $state->isFulltextSearch()) {
            return;
        }

        $state->getQuery()->whereNotIn('discussions.id', function ($query) {
            return $query->select('discussion_id')
            ->from('discussion_tag')
            ->whereIn('tag_id', Tag::where('is_hidden', 1)->pluck('id'));
        });
    }
}
