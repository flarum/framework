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
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'startUser' => true,
        'lastUser' => true,
        'startPost' => false,
        'lastPost' => false,
        'relevantPosts' => false
    ];

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public static $sortFields = ['lastTime', 'commentsCount', 'startTime'];

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

        static::addPaginationLinks($response, $request, route('flarum.api.discussions.index'), $total ?: $results->areMoreResults());

        return $results->getDiscussions();
    }
}
