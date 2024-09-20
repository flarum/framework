<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Search\AbstractFulltextFilter;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\SearchState;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
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
            'mysql' => $this->mysql($state, $value),
            'pgsql' => $this->pgsql($state, $value),
            'sqlite' => $this->sqlite($state, $value),
            default => throw new RuntimeException('Unsupported database driver: '.$state->getQuery()->getConnection()->getDriverName()),
        };
    }

    protected function sqlite(DatabaseSearchState $state, string $value): void
    {
        /** @var Builder $query */
        $query = $state->getQuery();

        $query->where(function (Builder $query) use ($state, $value) {
            $query->where('discussions.title', 'like', "%$value%")
                ->orWhereExists(function (QueryBuilder $query) use ($state, $value) {
                    $query->selectRaw('1')
                        ->from(
                            Post::whereVisibleTo($state->getActor())
                                ->whereColumn('discussion_id', 'discussions.id')
                                ->where('type', 'comment')
                                ->where('content', 'like', "%$value%")
                                ->limit(1)
                                ->toBase()
                        );
                });
        });
    }

    protected function mysql(DatabaseSearchState $state, string $value): void
    {
        /** @var Builder $query */
        $query = $state->getQuery();

        // Replace all non-word characters with spaces.
        // We do this to prevent MySQL fulltext search boolean mode from taking
        // effect: https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
        $value = preg_replace('/[^\p{L}\p{N}\p{M}_]+/u', ' ', $value);

        $grammar = $query->getGrammar();

        $match = 'MATCH('.$grammar->wrap('posts.content').') AGAINST (?)';
        $matchBooleanMode = 'MATCH('.$grammar->wrap('posts.content').') AGAINST (? IN BOOLEAN MODE)';
        $matchTitle = 'MATCH('.$grammar->wrap('discussions.title').') AGAINST (?)';
        $mostRelevantPostId = 'SUBSTRING_INDEX(GROUP_CONCAT('.$grammar->wrap('posts.id').' ORDER BY '.$match.' DESC, '.$grammar->wrap('posts.number').'), \',\', 1) as most_relevant_post_id';

        $discussionSubquery = Discussion::select('id')
            ->selectRaw('NULL as score')
            ->selectRaw('first_post_id as most_relevant_post_id')
            ->whereRaw($matchTitle, [$value]);

        // Construct a subquery to fetch discussions which contain relevant
        // posts. Retrieve the collective relevance of each discussion's posts,
        // which we will use later in the order by clause, and also retrieve
        // the ID of the most relevant post.
        $subquery = Post::whereVisibleTo($state->getActor())
            ->select('posts.discussion_id')
            ->selectRaw("SUM($match) as score", [$value])
            ->selectRaw($mostRelevantPostId, [$value])
            ->where('posts.type', 'comment')
            ->whereRaw($matchBooleanMode, [$value])
            ->groupBy('posts.discussion_id')
            ->union($discussionSubquery);

        // Join the subquery into the main search query and scope results to
        // discussions that have a relevant title or that contain relevant posts.
        $query
            ->addSelect('posts_ft.most_relevant_post_id')
            ->join(
                new Expression('('.$subquery->toSql().') '.$grammar->wrapTable('posts_ft')),
                'posts_ft.discussion_id',
                '=',
                'discussions.id'
            )
            ->groupBy('discussions.id')
            ->addBinding($subquery->getBindings(), 'join');

        $state->setDefaultSort(function (Builder $query) use ($value, $matchTitle) {
            $query->orderByRaw("$matchTitle desc", [$value]);
            $query->orderBy('posts_ft.score', 'desc');
        });
    }

    protected function pgsql(DatabaseSearchState $state, string $value): void
    {
        $searchConfig = $this->settings->get('pgsql_search_configuration');

        /** @var Builder $query */
        $query = $state->getQuery();

        $grammar = $query->getGrammar();

        $matchCondition = "to_tsvector('$searchConfig', ".$grammar->wrap('posts.content').") @@ plainto_tsquery('$searchConfig', ?)";
        $matchScore = "ts_rank(to_tsvector('$searchConfig', ".$grammar->wrap('posts.content')."), plainto_tsquery('$searchConfig', ?))";
        $matchTitleCondition = "to_tsvector('$searchConfig', ".$grammar->wrap('discussions.title').") @@ plainto_tsquery('$searchConfig', ?)";
        $matchTitleScore = "ts_rank(to_tsvector('$searchConfig', ".$grammar->wrap('discussions.title')."), plainto_tsquery('$searchConfig', ?))";
        $mostRelevantPostId = 'CAST(SPLIT_PART(STRING_AGG(CAST('.$grammar->wrap('posts.id')." AS VARCHAR), ',' ORDER BY ".$matchScore.' DESC, '.$grammar->wrap('posts.number')."), ',', 1) AS INTEGER) as most_relevant_post_id";

        $discussionSubquery = Discussion::select('id')
            ->selectRaw('NULL as score')
            ->selectRaw('first_post_id as most_relevant_post_id')
            ->whereRaw($matchTitleCondition, [$value]);

        // Construct a subquery to fetch discussions which contain relevant
        // posts. Retrieve the collective relevance of each discussion's posts,
        // which we will use later in the order by clause, and also retrieve
        // the ID of the most relevant post.
        $subquery = Post::whereVisibleTo($state->getActor())
            ->select('posts.discussion_id')
            ->selectRaw("SUM($matchScore) as score", [$value])
            ->selectRaw($mostRelevantPostId, [$value])
            ->where('posts.type', 'comment')
            ->whereRaw($matchCondition, [$value])
            ->groupBy('posts.discussion_id')
            ->union($discussionSubquery);

        // Join the subquery into the main search query and scope results to
        // discussions that have a relevant title or that contain relevant posts.
        $query
            ->distinct('discussions.id')
            ->addSelect('posts_ft.most_relevant_post_id')
            ->addSelect('posts_ft.score')
            ->join(
                new Expression('('.$subquery->toSql().') '.$grammar->wrapTable('posts_ft')),
                'posts_ft.discussion_id',
                '=',
                'discussions.id'
            )
            ->addBinding($subquery->getBindings(), 'join')
            ->orderBy('discussions.id');

        $state->setQuery(
            $query
                ->getModel()
                ->newQuery()
                ->select('*')
                ->fromSub($query, 'discussions')
        );

        $state->setDefaultSort(function (Builder $query) use ($value, $matchTitleScore) {
            $query->orderByRaw("$matchTitleScore desc", [$value]);
            $query->orderBy('discussions.score', 'desc');
        });
    }
}
