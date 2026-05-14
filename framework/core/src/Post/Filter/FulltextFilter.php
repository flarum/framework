<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Filter;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * @extends AbstractFulltextFilter<DatabaseSearchState>
 */
class FulltextFilter extends AbstractFulltextFilter
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function search(SearchState $state, string $value): void
    {
        match ($state->getQuery()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => $this->mysql($state, $value),
            'pgsql' => $this->pgsql($state, $value),
            'sqlite' => $this->sqlite($state, $value),
            default => throw new RuntimeException('Unsupported database driver: '.$state->getQuery()->getConnection()->getDriverName()),
        };
    }

    protected function sqlite(DatabaseSearchState $state, string $value): void
    {
        $state->getQuery()->where('content', 'like', "%$value%");
    }

    protected function mysql(DatabaseSearchState $state, string $value): void
    {
        $query = $state->getQuery();

        // Replace all non-word characters with spaces.
        // We do this to prevent MySQL fulltext search boolean mode from taking
        // effect: https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
        $value = preg_replace('/[^\p{L}\p{N}\p{M}_]+/u', ' ', $value);

        $grammar = $query->getGrammar();

        $match = 'MATCH('.$grammar->wrap('posts.content').') AGAINST (?)';
        $matchBooleanMode = 'MATCH('.$grammar->wrap('posts.content').') AGAINST (? IN BOOLEAN MODE)';

        $query->whereRaw($matchBooleanMode, [$value]);

        $state->setDefaultSort(function (Builder $query) use ($value, $match) {
            $query->orderByRaw($match.' desc', [$value]);
        });
    }

    protected function pgsql(DatabaseSearchState $state, string $value): void
    {
        $searchConfig = $this->settings->get('pgsql_search_configuration');

        $query = $state->getQuery();

        $grammar = $query->getGrammar();

        $matchCondition = 'to_tsvector(?::regconfig, '.$grammar->wrap('posts.content').') @@ plainto_tsquery(?::regconfig, ?)';
        $matchScore = 'ts_rank(to_tsvector(?::regconfig, '.$grammar->wrap('posts.content').'), plainto_tsquery(?::regconfig, ?))';

        $matchBindings = [$searchConfig, $searchConfig, $value];

        $query->whereRaw($matchCondition, $matchBindings);

        $state->setDefaultSort(function (Builder $query) use ($matchBindings, $matchScore) {
            $query->orderByRaw($matchScore.' desc', $matchBindings);
        });
    }
}
