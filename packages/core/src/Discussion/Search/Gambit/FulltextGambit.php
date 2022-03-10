<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Gambit;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;
use Illuminate\Database\Query\Expression;

class FulltextGambit implements GambitInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(SearchState $search, $bit)
    {
        // Replace all non-word characters with spaces.
        // We do this to prevent MySQL fulltext search boolean mode from taking
        // effect: https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
        $bit = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $bit);

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        $discussionSubquery = Discussion::select('id')
            ->selectRaw('NULL as score')
            ->selectRaw('first_post_id as most_relevant_post_id')
            ->whereRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (? IN BOOLEAN MODE)', [$bit]);

        // Construct a subquery to fetch discussions which contain relevant
        // posts. Retrieve the collective relevance of each discussion's posts,
        // which we will use later in the order by clause, and also retrieve
        // the ID of the most relevant post.
        $subquery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->selectRaw('SUM(MATCH('.$grammar->wrap('posts.content').') AGAINST (?)) as score', [$bit])
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT('.$grammar->wrap('posts.id').' ORDER BY MATCH('.$grammar->wrap('posts.content').') AGAINST (?) DESC, '.$grammar->wrap('posts.number').'), \',\', 1) as most_relevant_post_id', [$bit])
            ->where('posts.type', 'comment')
            ->whereRaw('MATCH('.$grammar->wrap('posts.content').') AGAINST (? IN BOOLEAN MODE)', [$bit])
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

        $search->setDefaultSort(function ($query) use ($grammar, $bit) {
            $query->orderByRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (?) desc', [$bit]);
            $query->orderBy('posts_ft.score', 'desc');
        });
    }
}
