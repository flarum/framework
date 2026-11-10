<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Search\Filter;

use Flarum\Http\BatchSlugDriverInterface;
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
use Illuminate\Support\Collection;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class TagFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected SlugManager $slugManager
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
        $inputSlugs = $this->asStringArray((array) $rawSlugs);

        // Collect every non-"untagged" slug across all OR groups so we can
        // resolve them in as few queries as the driver allows.
        $allSlugs = [];
        foreach ($inputSlugs as $orSlugs) {
            foreach (explode(',', $orSlugs) as $slug) {
                if ($slug !== 'untagged') {
                    $allSlugs[] = $slug;
                }
            }
        }

        // Resolve slugs → IDs via the active slug driver, so custom drivers (which
        // may not use the literal `slug` column) work. Slugs the actor cannot see,
        // or that don't resolve, are simply absent from the map (treated as
        // unknown), producing the same null-ID / no-match behaviour as before.
        $slugToId = $this->resolveSlugs(array_unique($allSlugs), $actor);

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
                        $id = $slugToId->get($slug);

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

    /**
     * Resolve raw tag slugs to a `slug => id` map using the active slug driver.
     * Drivers that can resolve in bulk ({@link BatchSlugDriverInterface}) do so
     * in a single query; others fall back to per-slug resolution.
     *
     * @param string[] $slugs
     * @return Collection<string, int>
     */
    protected function resolveSlugs(array $slugs, User $actor): Collection
    {
        /** @var Collection<string, int> $map */
        $map = new Collection();

        if (! $slugs) {
            return $map;
        }

        $driver = $this->slugManager->forResource(Tag::class);

        if ($driver instanceof BatchSlugDriverInterface) {
            foreach ($driver->fromSlugs($slugs, $actor) as $slug => $tag) {
                /** @var Tag $tag */
                $map[$slug] = (int) $tag->id;
            }

            return $map;
        }

        foreach ($slugs as $slug) {
            try {
                /** @var Tag $tag */
                $tag = $driver->fromSlug($slug, $actor);
                $map[$slug] = (int) $tag->id;
            } catch (ModelNotFoundException) {
                // Slug does not resolve to a visible tag; skip it.
            }
        }

        return $map;
    }
}
