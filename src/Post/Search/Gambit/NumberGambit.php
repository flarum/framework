<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Search\Gambit;

use Flarum\Post\Search\PostSearch;
use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\AbstractSearch;
use LogicException;

class NumberGambit extends AbstractRegexGambit
{
    /**
     * {@inheritdoc}
     */
    protected $pattern = 'number:(\d+)';

    /**
     * {@inheritdoc}
     */
    protected function conditions(AbstractSearch $search, array $matches, $negate)
    {
        if (!$search instanceof PostSearch) {
            throw new LogicException('This gambit can only be applied on a PostSearch');
        }

        $number = trim($matches[1], '');

        $search->getQuery()->where('posts.number', $number);
    }
}
