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
use Flarum\Post\Post;
use Flarum\Search\AbstractSearch;
use Flarum\Search\GambitInterface;
use Illuminate\Database\Query\Expression;
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

        // Replace all non-word characters with spaces.
        // We do this to prevent MySQL fulltext search boolean mode from taking
        // effect: https://dev.mysql.com/doc/refman/5.7/en/fulltext-boolean.html
        $bit = preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $bit);

        $query = $search->getQuery();
        $grammar = $query->getGrammar();

        // Construct a subquery to fetch discussions which contain relevant
        // posts.
        $subquery = Post::whereVisibleTo($search->getActor())
            ->select('posts.discussion_id')
            ->from('posts')
            ->where('posts.content','like', '%' . $bit . '%')
            ->where('posts.type', '=', 'comment')
            ->where('posts.is_private', '=', 0)
            ->orderBy('id');
        $query
            ->where(function ($query) use ($subquery, $bit) {
                $query
                    ->where('id', 'in', $subquery)
                    ->orWhere('discussions.title', 'like', '%' . $bit . '%');
            })
            ->where('discussions.is_private', '=', 0)
            ->orderBy('discussions.last_posted_at', 'desc');
    }
}
