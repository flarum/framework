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
    /**
     * @var TagRepository
     */
    protected $tags;

    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    private function getTagSearchSubQuery(string $searchValue): Builder
    {
        return $this->tags
            ->query()
            ->select('id')
            ->where('name', 'like', "$searchValue%")
            ->orWhere('slug', 'like', "$searchValue%");
    }

    public function apply(SearchState $search, $searchValue)
    {
        $search->getQuery()
            ->whereIn(
                'id',
                $this->getTagSearchSubQuery($searchValue)
            );

        return true;
    }
}
