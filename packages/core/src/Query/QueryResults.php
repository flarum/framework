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
    /**
     * @var Collection
     */
    protected $results;

    /**
     * @var bool
     */
    protected $areMoreResults;

    /**
     * @param Collection $results
     * @param bool $areMoreResults
     */
    public function __construct(Collection $results, $areMoreResults)
    {
        $this->results = $results;
        $this->areMoreResults = $areMoreResults;
    }

    /**
     * @return Collection
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @return bool
     */
    public function areMoreResults()
    {
        return $this->areMoreResults;
    }
}
