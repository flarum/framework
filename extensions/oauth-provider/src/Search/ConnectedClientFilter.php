<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Search;

use Flarum\OAuthProvider\Models\Consent;
use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;

/**
 * The members who have connected an application (`filter[oauthClient]=<client id>`),
 * or with `-oauthClient` the ones who haven't. Who connected what is for
 * admins, as managing clients is: anyone else finds nobody.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class ConnectedClientFilter implements FilterInterface
{
    public function getFilterKey(): string
    {
        return 'oauthClient';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $query = $state->getQuery();

        if (! $state->getActor()->isAdmin()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $connected = Consent::query()
            ->select('user_id')
            ->whereIn('client_id', (array) $value)
            ->where('revoked', false);

        $query->whereIn($query->getModel()->qualifyColumn('id'), $connected, not: $negate);
    }
}
