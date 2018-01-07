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

        // TODO: add a migration for fulltext index on discussions.title
        $search->getQuery()
            ->selectRaw('SUBSTRING_INDEX(GROUP_CONCAT(posts.id ORDER BY MATCH(posts.content) AGAINST (?) DESC), \',\', 1) as most_relevant_post_id', [$bit])
            ->join('posts', function ($join) use ($bit) {
                $join->on('posts.discussion_id', '=', 'discussions.id')
                    ->whereRaw('MATCH(posts.content) AGAINST (? IN BOOLEAN MODE)', [$bit]);

                // TODO: need to scope post visibility
            })
            ->whereRaw('MATCH(discussions.title) AGAINST (? IN BOOLEAN MODE)', [$bit])
            ->orWhereNotNull('posts.id')
            ->groupBy('discussions.id');

        $search->setDefaultSort(function ($query) use ($bit) {
            $query->orderByRaw('MATCH(discussions.title) AGAINST (?) desc', [$bit]);
            $query->orderByRaw('SUM(MATCH(posts.content) AGAINST (?)) desc', [$bit]);
        });
    }
}
