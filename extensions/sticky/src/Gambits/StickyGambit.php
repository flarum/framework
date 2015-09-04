<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Sticky\Gambits;

use Flarum\Core\Search\Search;
use Flarum\Core\Search\RegexGambit;

class StickyGambit extends RegexGambit
{
    /**
     * The gambit's regex pattern.
     *
     * @var string
     */
    protected $pattern = 'is:sticky';

    /**
     * Apply conditions to the searcher, given matches from the gambit's
     * regex.
     *
     * @param array $matches The matches from the gambit's regex.
     * @param \Flarum\Core\Search\SearcherInterface $searcher
     * @return void
     */
    protected function conditions(Search $search, array $matches, $negate)
    {
        $search->getQuery()->where('is_sticky', ! $negate);
    }
}
