<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search\Filter;

use Flarum\Http\SlugManager;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class TagFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected SlugManager $slugger
    ) {
    }

    public function getFilterKey(): string
    {
        return 'tag';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $this->constrain($state->getQuery(), $value, $negate, $state->getActor());
    }

    protected function constrain(Builder $query, string|array $rawSlugs, bool $negate, User $actor): void
    {
        $rawSlugs = (array) $rawSlugs;

        $inputSlugs = $this->asStringArray($rawSlugs);

        foreach ($inputSlugs as $orSlugs) {
            $slugs = explode(',', $orSlugs);

            $query->where(function (Builder $query) use ($slugs, $negate, $actor) {
                foreach ($slugs as $slug) {
                    if ($slug === 'untagged') {
                        $query->whereIn('discussions.id', function (QueryBuilder $query) {
                            $query->select('discussion_id')
                                ->from('discussion_tag');
                        }, 'or', ! $negate);
                    } else {
                        // @TODO: grab all IDs first instead of multiple queries.
                        try {
                            $id = $this->slugger->forResource(Tag::class)->fromSlug($slug, $actor)->id;
                        } catch (ModelNotFoundException) {
                            $id = null;
                        }

                        $query->whereIn('discussions.id', function (QueryBuilder $query) use ($id) {
                            $query->select('discussion_id')
                                ->from('discussion_tag')
                                ->where('tag_id', $id);
                        }, 'or', $negate);
                    }
                }
            });
        }
    }
}
