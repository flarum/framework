<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Search;

interface Gambit
{
    /**
     * Apply conditions to the searcher for a bit of the search string.
     *
     * @param Search $search
     * @param string $bit The piece of the search string.
     * @return bool Whether or not the gambit was active for this bit.
     */
    public function apply(Search $search, $bit);
}
