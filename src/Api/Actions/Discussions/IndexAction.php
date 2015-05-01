<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Search\Discussions\DiscussionSearchCriteria;
use Flarum\Core\Search\Discussions\DiscussionSearcher;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

class IndexAction extends SerializeCollectionAction
{
    /**
     * The discussion searcher.
     *
     * @var \Flarum\Core\Search\Discussions\DiscussionSearcher
     */
    protected $searcher;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * The relations that are available to be included.
     *
     * @var array
     */
    public static $includeAvailable = ['startUser', 'lastUser', 'startPost', 'lastPost', 'relevantPosts'];

    /**
     * The relations that are included by default.
     *
     * @var array
     */
    public static $include = ['startUser', 'lastUser'];

    /**
     * The maximum number of records that can be requested.
     *
     * @var integer
     */
    public static $limitMax = 50;

    /**
     * The number of records included by default.
     *
     * @var integer
     */
    public static $limit = 20;

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public static $sortAvailable = ['lastTime', 'commentsCount', 'startTime'];

    /**
     * The default field to sort by.
     *
     * @var string
     */
    public static $sort = ['lastTime' => 'desc'];

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Search\Discussions\DiscussionSearcher $searcher
     */
    public function __construct(DiscussionSearcher $searcher)
    {
        $this->searcher = $searcher;
    }

    /**
     * Get the discussion results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $criteria = new DiscussionSearchCriteria(
            $request->actor->getUser(),
            $request->get('q'),
            $request->sort
        );

        $load = array_merge($request->include, ['state']);
        $results = $this->searcher->search($criteria, $request->limit, $request->offset, $load);

        if (($total = $results->getTotal()) !== null) {
            $response->content->addMeta('total', $total);
        }

        // $response->content->addMeta('moreUrl', $moreUrl);

        return $results->getDiscussions();
    }
}
