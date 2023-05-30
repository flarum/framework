<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Query;

use Illuminate\Database\Eloquent\Collection;

class QueryResults
{
    public function __construct(
        protected Collection $results,
        protected bool $areMoreResults
    ) {
    }

    public function getResults(): Collection
    {
        return $this->results;
    }

    public function areMoreResults(): bool
    {
        return $this->areMoreResults;
    }
}
