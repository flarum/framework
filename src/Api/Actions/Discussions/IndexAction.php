<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Search\Discussions\DiscussionSearchCriteria;
use Flarum\Core\Search\Discussions\DiscussionSearcher;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Http\UrlGeneratorInterface;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * The discussion searcher.
     *
     * @var \Flarum\Core\Search\Discussions\DiscussionSearcher
     */
    protected $searcher;

    /**
     * The URL generator.
     *
     * @var \Flarum\Http\UrlGeneratorInterface
     */
    protected $url;

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
        'relevantPosts' => false,
        'relevantPosts.discussion' => false,
        'relevantPosts.user' => false
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
     * @param  \Flarum\Http\UrlGeneratorInterface  $url
     */
    public function __construct(DiscussionSearcher $searcher, UrlGeneratorInterface $url)
    {
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * Get the discussion results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $criteria = new DiscussionSearchCriteria(
            $request->actor->getUser(),
            $request->get('q'),
            $request->sort
        );

        $load = array_merge($request->include, ['state']);
        $results = $this->searcher->search($criteria, $request->limit, $request->offset, $load);

        if (($total = $results->getTotal()) !== null) {
            $document->addMeta('total', $total);
        }

        static::addPaginationLinks(
            $document,
            $request,
            $this->url->toRoute('flarum.api.discussions.index'),
            $total ?: $results->areMoreResults()
        );

        return $results->getDiscussions();
    }
}
