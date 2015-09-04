<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Lock\Gambits;

use Flarum\Core\Search\Search;
use Flarum\Core\Search\RegexGambit;

class LockGambit extends RegexGambit
{
    protected $pattern = 'is:locked';

    protected function conditions(Search $search, array $matches, $negate)
    {
        $search->getQuery()->where('is_locked', ! $negate);
    }
}
