<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Subscriptions;

use Flarum\Query\QueryCriteria;
use Flarum\Search\SearchState;

class HideIgnoredFromAllDiscussionsPage
{
    public function __invoke(SearchState $state, QueryCriteria $criteria): void
    {
        // We only want to hide on the "all discussions" page.
        if (count($state->getActiveFilters()) === 0 && ! $state->isFulltextSearch()) {
            // TODO: might be better as `id IN (subquery)`?
            $actor = $state->getActor();
            $state->getQuery()->whereNotExists(function ($query) use ($actor) {
                $query->selectRaw(1)
                    ->from('discussion_user')
                    ->whereColumn('discussions.id', 'discussion_id')
                    ->where('user_id', $actor->id)
                    ->where('subscription', 'ignore');
            });
        }
    }
}
