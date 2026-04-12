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
use Flarum\Tags\Tag;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class TagFilter implements FilterInterface
{
    use ValidateFilterTrait;

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
        $inputSlugs = $this->asStringArray((array) $rawSlugs);

        // Collect every non-"untagged" slug across all OR groups so we can
        // resolve them all in one query instead of one per slug.
        // urldecode() matches the behaviour of Utf8SlugDriver::fromSlug().
        $allSlugs = [];
        foreach ($inputSlugs as $orSlugs) {
            foreach (explode(',', $orSlugs) as $slug) {
                if ($slug !== 'untagged') {
                    $allSlugs[] = urldecode($slug);
                }
            }
        }

        // Single batch query: resolve slugs → IDs, respecting actor visibility.
        // Slugs the actor cannot see are simply absent from the map (treated as
        // unknown), which produces the same null-ID / no-match behaviour as before.
        $slugToId = $allSlugs
            ? Tag::query()
                ->whereIn('slug', array_unique($allSlugs))
                ->whereVisibleTo($actor)
                ->pluck('id', 'slug')
            : new Collection();

        foreach ($inputSlugs as $orSlugs) {
            $slugs = explode(',', $orSlugs);

            $query->where(function (Builder $query) use ($slugs, $negate, $slugToId) {
                foreach ($slugs as $slug) {
                    if ($slug === 'untagged') {
                        $query->whereIn('discussions.id', function (QueryBuilder $query) {
                            $query->select('discussion_id')
                                ->from('discussion_tag');
                        }, 'or', ! $negate);
                    } else {
                        $id = $slugToId->get(urldecode($slug));

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
