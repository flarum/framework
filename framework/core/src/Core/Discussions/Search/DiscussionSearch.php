<?php namespace Flarum\Core\Discussions\Search;

use Flarum\Core\Search\Search;

/**
 * An object which represents the internal state of a search for discussions:
 * the search query, the user performing the search, the fallback sort order,
 * relevant post information, and a log of which gambits have been used.
 */
class DiscussionSearch extends Search
{
    /**
     * {@inheritdoc}
     */
    protected $defaultSort = ['lastTime' => 'desc'];

    /**
     * @var array
     */
    protected $relevantPostIds = [];

    /**
     * Get the related IDs for each result.
     *
     * @return int[]
     */
    public function getRelevantPostIds()
    {
        return $this->relevantPostIds;
    }

    /**
     * Set the relevant post IDs for a result.
     *
     * @param int $discussionId
     * @param int[] $postIds
     * @return void
     */
    public function setRelevantPostIds($discussionId, array $postIds)
    {
        $this->relevantPostIds[$discussionId] = $postIds;
    }

    /**
     * Add a relevant post ID for a discussion result.
     *
     * @param int $discussionId
     * @param int $postId
     * @return void
     */
    public function addRelevantPostId($discussionId, $postId)
    {
        $this->relevantPostIds[$discussionId][] = $postId;
    }
}
