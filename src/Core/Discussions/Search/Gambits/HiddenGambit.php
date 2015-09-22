<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Discussions\Search\Gambits;

use Flarum\Core\Discussions\Search\DiscussionSearch;
use Flarum\Core\Search\RegexGambit;
use Flarum\Core\Search\Search;
use LogicException;

class HiddenGambit extends RegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'is:hidden';

    /**
     * {@inheritdoc}
     */
    protected function conditions(Search $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $search->getQuery()->where(function ($query) use ($negate) {
            if ($negate) {
                $query->whereNull('hide_time')->where('comments_count', '>', 0);
            } else {
                $query->whereNotNull('hide_time')->orWhere('comments_count', 0);
            }
        });
    }
}
