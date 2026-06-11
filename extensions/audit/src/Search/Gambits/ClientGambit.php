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

class ClientGambit extends AbstractRegexGambit
{
    protected function getGambitPattern(): string
    {
        return 'client:(.+)';
    }

    protected function conditions(SearchState $search, array $matches, $negate)
    {
        $clients = explode(',', trim($matches[1], '"'));

        $search->getQuery()->whereIn('client', $clients, 'and', $negate);
    }
}
