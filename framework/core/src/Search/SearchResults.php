<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

use Closure;
use Illuminate\Database\Eloquent\Collection;

class SearchResults
{
    public function __construct(
        protected Collection $results,
        protected bool $areMoreResults,
        /** @var Closure(): int */
        protected Closure $totalResults
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

    public function getTotalResults(): int
    {
        return ($this->totalResults)();
    }
}
