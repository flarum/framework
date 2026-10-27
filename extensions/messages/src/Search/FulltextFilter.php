<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\Search;

use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use RuntimeException;

/**
 * Searches private messages by what they say, the way core searches posts:
 * the database's own fulltext search where it has one, ranked by relevance,
 * and a substring match on SQLite or in CJK mode. Which messages can be found
 * at all is decided by the searcher, which only sees the actor's conversations.
 *
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
        if ($this->settings->get('search_cjk_mode')) {
            $this->like($state, $value);

            return;
        }

        match ($state->getQuery()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => $this->mysql($state, $value),
            'pgsql' => $this->pgsql($state, $value),
            'sqlite' => $this->like($state, $value),
            default => throw new RuntimeException('Unsupported database driver: '.$state->getQuery()->getConnection()->getDriverName()),
        };
    }

    protected function like(DatabaseSearchState $state, string $value): void
    {
        $state->getQuery()->where('dialog_messages.content', 'like', "%$value%");
    }

    protected function mysql(DatabaseSearchState $state, string $value): void
    {
        $query = $state->getQuery();

        // Non-word characters would otherwise act as boolean mode operators.
        $value = preg_replace('/[^\p{L}\p{N}\p{M}_]+/u', ' ', $value);

        $grammar = $query->getGrammar();

        $match = 'MATCH('.$grammar->wrap('dialog_messages.content').') AGAINST (?)';
        $matchBooleanMode = 'MATCH('.$grammar->wrap('dialog_messages.content').') AGAINST (? IN BOOLEAN MODE)';

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

        $matchCondition = 'to_tsvector(?::regconfig, '.$grammar->wrap('dialog_messages.content').') @@ plainto_tsquery(?::regconfig, ?)';
        $matchScore = 'ts_rank(to_tsvector(?::regconfig, '.$grammar->wrap('dialog_messages.content').'), plainto_tsquery(?::regconfig, ?))';

        $matchBindings = [$searchConfig, $searchConfig, $value];

        $query->whereRaw($matchCondition, $matchBindings);

        $state->setDefaultSort(function (Builder $query) use ($matchBindings, $matchScore) {
            $query->orderByRaw($matchScore.' desc', $matchBindings);
        });
    }
}
