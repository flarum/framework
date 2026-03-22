<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchCriteria;
use Flarum\Tags\Tag;

class HideHiddenTagsFromAllDiscussionsPage
{
    public function __invoke(DatabaseSearchState $state, SearchCriteria $queryCriteria): void
    {
        if (count($state->getActiveFilters()) > 0 || $state->isFulltextSearch()) {
            return;
        }

        $hiddenTagIds = Tag::where('is_hidden', 1)->pluck('id');

        $applyFilter = function ($query) use ($hiddenTagIds) {
            $query->whereNotIn('discussions.id', function ($q) use ($hiddenTagIds) {
                return $q->select('discussion_id')
                    ->from('discussion_tag')
                    ->whereIn('tag_id', $hiddenTagIds);
            });
        };

        $eloquentQuery = $state->getQuery();
        $applyFilter($eloquentQuery);

        // Also apply to any existing union queries (e.g. from PinStickiedDiscussionsToTop)
        // in case that mutator ran before this one.
        foreach ($eloquentQuery->getQuery()->unions ?? [] as $union) {
            $applyFilter($union['query']);
        }
    }
}
