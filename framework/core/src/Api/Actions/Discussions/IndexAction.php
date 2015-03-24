<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Search\Discussions\DiscussionSearchCriteria;
use Flarum\Core\Search\Discussions\DiscussionSearcher;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\DiscussionSerializer;

class IndexAction extends BaseAction
{
    /**
     * The discussion searcher.
     *
     * @var \Flarum\Core\Search\Discussions\DiscussionSearcher
     */
    protected $searcher;

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Search\Discussions\DiscussionSearcher $searcher
     */
    public function __construct(Actor $actor, DiscussionSearcher $searcher)
    {
        $this->actor = $actor;
        $this->searcher = $searcher;
    }

    /**
     * Show a list of discussions.
     *
     * @return \Illuminate\Http\Response
     */
    protected function run(ApiParams $params)
    {
        $query   = $params->get('q');
        $start   = $params->start();
        $include = $params->included(['startUser', 'lastUser', 'startPost', 'lastPost', 'relevantPosts']);
        $count   = $params->count(20, 50);
        $sort    = $params->sort(['', 'lastPost', 'replies', 'created']);

        // Set up the discussion searcher with our search criteria, and get the
        // requested range of results with the necessary relations loaded.
        $criteria = new DiscussionSearchCriteria($this->actor->getUser(), $query, $sort['field'], $sort['order']);
        $load = array_merge($include, ['state']);

        $results = $this->searcher->search($criteria, $count, $start, $load);

        $document = $this->document();

        if (($total = $results->getTotal()) !== null) {
            $document->addMeta('total', $total);
        }

        // If there are more results, then we need to construct a URL to the
        // next results page and add that to the metadata. We do this by
        // compacting all of the valid query parameters which have been
        // specified.
        if ($results->areMoreResults()) {
            $start += $count;
            $sort = $sort['string'];
            $input = array_filter(compact('query', 'sort', 'start', 'count')) + ['include' => implode(',', $include)];
            $moreUrl = $this->buildUrl('discussions.index', [], $input);
        } else {
            $moreUrl = '';
        }
        $document->addMeta('moreUrl', $moreUrl);

        // Finally, we can set up the discussion serializer and use it to create
        // a collection of discussion results.
        $serializer = new DiscussionSerializer($include);
        $document->setData($serializer->collection($results->getDiscussions()));

        return $this->respondWithDocument($document);
    }
}
