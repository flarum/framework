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

        $query->where(function (Builder $query) use ($slugs, $negate) {
            foreach ($slugs as $slug) {
                if ($slug === 'untagged') {
                    $query->whereIn('discussions.id', function (Builder $query) {
                        $query->select('discussion_id')
                            ->from('discussion_tag');
                    }, 'or', ! $negate);
                } else {
                    $id = $this->tags->getIdForSlug($slug);

                    $query->whereIn('discussions.id', function (Builder $query) use ($id) {
                        $query->select('discussion_id')
                            ->from('discussion_tag')
                            ->where('tag_id', $id);
                    }, 'or', $negate);
                }
            }
        });
    }
}
