<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

interface SearcherInterface
{
    public function getQuery(User $actor): Builder;

    public function search(SearchCriteria $criteria): SearchResults;
}
