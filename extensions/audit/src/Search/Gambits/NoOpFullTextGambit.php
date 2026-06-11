<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Gambits;

use Flarum\Search\GambitInterface;
use Flarum\Search\SearchState;

class NoOpFullTextGambit implements GambitInterface
{
    public function apply(SearchState $search, $bit)
    {
        // Doesn't do anything for now, but required by the Search API
        return false;
    }
}
