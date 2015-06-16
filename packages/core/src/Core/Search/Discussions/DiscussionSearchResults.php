<?php namespace Flarum\Core\Search\Discussions;

class DiscussionSearchResults
{
    protected $discussions;

    protected $areMoreResults;

    public function __construct($discussions, $areMoreResults)
    {
        $this->discussions = $discussions;
        $this->areMoreResults = $areMoreResults;
    }

    public function getDiscussions()
    {
        return $this->discussions;
    }

    public function areMoreResults()
    {
        return $this->areMoreResults;
    }
}
