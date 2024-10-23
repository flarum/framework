<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search\Database;

use Flarum\Search\SearchState;
use Illuminate\Database\Eloquent\Builder;

class DatabaseSearchState extends SearchState
{
    protected Builder $query;

    public function setQuery(Builder $query): void
    {
        $this->query = $query;
    }

    public function getQuery(): Builder
    {
        return $this->query;
    }
}
