<?php namespace Flarum\Core\Search;

use Illuminate\Database\Eloquent\Collection;

class SearchResults
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
