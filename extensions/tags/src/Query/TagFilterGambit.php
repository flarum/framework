<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Query;

use Flarum\Filter\FilterInterface;
use Flarum\Filter\FilterState;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;
use Flarum\Tags\TagRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;

class TagFilterGambit extends AbstractRegexGambit implements FilterInterface
{
    /**
     * @var TagRepository
     */
    protected $tags;

    /**
     * @param TagRepository $tags
     */
    public function __construct(TagRepository $tags)
    {
        $this->tags = $tags;
    }

    protected function getGambitPattern()
    {
        return 'tag:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $this->constrain($search->getQuery(), $matches[1], $negate);
    }

    public function getFilterKey(): string
    {
        return 'tag';
    }

    public function filter(FilterState $filterState, string $filterValue, bool $negate)
    {
        $this->constrain($filterState->getQuery(), $filterValue, $negate);
    }

    protected function constrain(Builder $query, $rawSlugs, $negate)
    {
        $slugs = explode(',', trim($rawSlugs, '"'));

        // we need to alias the table this method is used twice (tag:support tag:solved)
        // aliases can be max 256, md5 is max 32
        $alias = 'dt_'.md5(intval($negate).$rawSlugs);

        $query
            ->distinct()
            ->leftJoin("discussion_tag as $alias", function (JoinClause $join) use ($slugs, $negate, $alias) {
                $join
                    ->on('discussions.id', '=', "$alias.discussion_id")
                    ->where(function (JoinClause $join) use ($slugs, $negate, $alias) {
                        foreach ($slugs as $slug) {
                            if ($slug === 'untagged' && ! $negate) {
                                $join->orWhereNull("$alias.tag_id");
                            } elseif ($slug === 'untagged' && $negate) {
                                $join->orWhereNotNull("$alias.tag_id");
                            } elseif ($id = $this->tags->getIdForSlug($slug)) {
                                $join->orWhere(
                                    "$alias.tag_id",
                                    $negate ? '!=' : '=',
                                    $id
                                );
                            }
                        }
                    });
            });
    }
}
