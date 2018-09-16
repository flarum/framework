<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Discussion\Search\Gambit;

use Flarum\Discussion\Search\DiscussionSearch;
use Flarum\Event\ScopeModelVisibility;
use Flarum\Post\Post;
use Flarum\Search\AbstractSearch;
use Flarum\Search\GambitInterface;
use LogicException;

class FulltextGambit implements GambitInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(AbstractSearch $search, $bit)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        // The @ character crashes fulltext searches on InnoDB tables.
        // See https://bugs.mysql.com/bug.php?id=74042
        $bit = str_replace('@', '*', $bit);

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        $query
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT('.$grammar->wrap('posts.id').' ORDER BY MATCH('.$grammar->wrap('posts.content').') AGAINST (?) DESC), \',\', 1) as most_relevant_post_id', [$bit])
            ->leftJoin('posts', 'posts.discussion_id', '=', 'discussions.id')
            ->where('posts.type', 'comment')
            ->where(function ($query) use ($search) {
                event(new ScopeModelVisibility(Post::query()->setQuery($query), $search->getActor(), 'view'));
            })
            ->where(function ($query) use ($bit) {
                $grammar = $query->getGrammar();

                $query->whereRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (? IN BOOLEAN MODE)', [$bit])
                    ->orWhereRaw('MATCH('.$grammar->wrap('posts.content').') AGAINST (? IN BOOLEAN MODE)', [$bit]);
            })
            ->groupBy('posts.discussion_id');

        $search->setDefaultSort(function ($query) use ($bit) {
            $grammar = $query->getGrammar();

            $query->orderByRaw('MATCH('.$grammar->wrap('discussions.title').') AGAINST (?) desc', [$bit]);
            $query->orderByRaw('MATCH('.$grammar->wrap('posts.content').') AGAINST (?) desc', [$bit]);
        });
    }
}
