<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search\Gambit;

use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Flarum\Tags\TagRepository;
use Illuminate\Database\Eloquent\Builder;

class FulltextGambit implements GambitInterface
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

    public function apply(SearchState $search, string $bit): bool
    {
        $search->getQuery()
            ->whereIn(
                'id',
                $this->getTagSearchSubQuery($bit)
            );

        return true;
    }
}
