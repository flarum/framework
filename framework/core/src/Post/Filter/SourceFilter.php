<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Posts by how they were made: `source:api`, `source:mcp,api`, `-source:mcp`.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class SourceFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function getFilterKey(): string
    {
        return 'source';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        // When the forum hides sources, filtering on them would reveal them.
        if (! $this->settings->get('show_post_source')) {
            return;
        }

        // Keys are lowercase; lowering the input keeps matching the same on every database.
        $sources = array_values(array_filter(
            array_map(fn (string $source) => strtolower(trim($source)), $this->asStringArray($value)),
            fn (string $source) => $source !== ''
        ));

        if (! $negate) {
            $state->getQuery()->whereIn('posts.source', $sources);

            return;
        }

        // NOT IN never matches NULL, which is every post made in the forum itself.
        $state->getQuery()->where(function (Builder $query) use ($sources) {
            $query->whereNull('posts.source')->orWhereNotIn('posts.source', $sources);
        });
    }
}
