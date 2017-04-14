<?php
/*
 * This file is part of Flarum.
 *
 * (c) Kevin Dierkx <contact@kevindierkx.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search\Discussion\Gambit;

use Flarum\Core\Search\Discussion\DiscussionSearch;
use Flarum\Core\Search\AbstractRegexGambit;
use Flarum\Core\Search\AbstractSearch;
use LogicException;

class TitleGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'title:(.+)';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (! $search instanceof DiscussionSearch) {
            throw new LogicException('This gambit can only be applied on a DiscussionSearch');
        }

        $title = trim($matches[1], '"');

        $search->getQuery()->where('title', $negate ? 'NOT LIKE' : 'LIKE', '%'.$title.'%');
    }
}
