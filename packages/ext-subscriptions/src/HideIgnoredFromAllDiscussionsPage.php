<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions;

use Flarum\Filter\FilterState;
use Flarum\Query\QueryCriteria;

class HideIgnoredFromAllDiscussionsPage
{
    public function __invoke(FilterState $filterState, QueryCriteria $criteria)
    {
        // We only want to hide on the "all discussions" page.
        if (count($filterState->getActiveFilters()) === 0) {
            // TODO: might be better as `id IN (subquery)`?
            $actor = $filterState->getActor();
            $filterState->getQuery()->whereNotExists(function ($query) use ($actor) {
                $query->selectRaw(1)
                    ->from('discussion_user')
                    ->whereColumn('discussions.id', 'discussion_id')
                    ->where('user_id', $actor->id)
                    ->where('subscription', 'ignore');
            });
        }
    }
}
