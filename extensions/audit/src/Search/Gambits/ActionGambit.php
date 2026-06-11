<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search\Gambits;

use Flarum\Search\AbstractRegexGambit;
use Flarum\Search\SearchState;

class ActionGambit extends AbstractRegexGambit
{
    protected function getGambitPattern(): string
    {
        return 'action:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $actions = explode(',', trim($matches[1], '"'));

        $search->getQuery()->whereIn('action', $actions, 'and', $negate);
    }
}
