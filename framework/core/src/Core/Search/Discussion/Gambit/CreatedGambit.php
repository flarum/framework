<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion\Gambit;

use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use Flarum\Core\Search\Discussion\DiscussionSearch;
use LogicException;

class CreatedGambit extends AbstractRegexGambit
{
    /**
     * http://stackoverflow.com/a/8270148/3158312
     *
     * {@inheritdoc}
     */
    protected $pattern = 'created:(\d{4}\-\d\d\-\d\d)(\.\.(\d{4}\-\d\d\-\d\d))?';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        if (empty($matches[4])) { // Single date
            $search->getQuery()->whereDate('start_time', $negate ? '!=' : '=', $matches[2]);
        } else { // Range: date..date
            $search->getQuery()->whereBetween('start_time', [$matches[2], $matches[4]], 'and', $negate);
        }
    }
}
