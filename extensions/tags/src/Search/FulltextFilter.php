<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\Tags\TagRepository;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class FulltextFilter extends AbstractFulltextFilter
{
    public function __construct(
        protected TagRepository $tags
    ) {
    }

    private function getTagSearchSubQuery(string $searchValue): Builder
    {
        return $this->tags
            ->query()
            ->select('id')
            ->where('name', 'like', "$searchValue%")
            ->orWhere('slug', 'like', "$searchValue%");
    }

    public function search(SearchState $state, string $value): void
    {
        $state->getQuery()
            ->whereIn(
                'id',
                $this->getTagSearchSubQuery($value)
            );
    }
}
