<?php namespace Flarum\Core\Search\Discussions;

class DiscussionSearchResults
{
    protected $discussions;

    protected $areMoreResults;

    protected $total;

    public function __construct($discussions, $areMoreResults, $total)
    {
        $this->discussions = $discussions;
        $this->areMoreResults = $areMoreResults;
        $this->total = $total;
    }

    public function getDiscussions()
    {
        return $this->discussions;
    }

	public function getTotal()
    {
        return $this->total;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }
}
