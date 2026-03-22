<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Http\SlugManager;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\User\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * @implements FilterInterface<DatabaseSearchState>
 */
class AuthorFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected SlugManager $slugManager
    ) {
    }

    public function getFilterKey(): string
    {
        return 'author';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $slugDriver = $this->slugManager->forResource(User::class);
        $ids = [];

        foreach ($this->asStringArray($value) as $slug) {
            try {
                $ids[] = $slugDriver->fromSlug($slug, $state->getActor())->id;
            } catch (ModelNotFoundException) {
                // Slug does not match any user; skip it.
            }
        }

        $state->getQuery()->whereIn('posts.user_id', $ids, 'and', $negate);
    }
}
