<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Search\Users\UserSearchCriteria;
use Flarum\Core\Search\Users\UserSearcher;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\UserSerializer;

class IndexAction extends BaseAction
{
    /**
     * The user searcher.
     *
     * @var \Flarum\Core\Search\Discussions\UserSearcher
     */
    protected $searcher;

    /**
     * Instantiate the action.
     *
     * @param  \Flarum\Core\Search\Discussions\UserSearcher  $searcher
     */
    public function __construct(Actor $actor, UserSearcher $searcher)
    {
        $this->actor = $actor;
        $this->searcher = $searcher;
    }

    /**
     * Show a list of users.
     *
     * @return \Illuminate\Http\Response
     */
    protected function run(ApiParams $params)
    {
        $query   = $params->get('q');
        $start   = $params->start();
        $include = $params->included(['groups']);
        $count   = $params->count(20, 50);
        $sort    = $params->sort(['', 'username', 'posts', 'discussions', 'lastActive', 'created']);

        $relations = array_merge(['groups'], $include);

        // Set up the user searcher with our search criteria, and get the
        // requested range of results with the necessary relations loaded.
        $criteria = new UserSearchCriteria($this->actor->getUser(), $query, $sort['field'], $sort['order']);

        $results = $this->searcher->search($criteria, $count, $start, $relations);

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
            $include = implode(',', $include);
            $sort = $sort['string'];
            $input = array_filter(compact('query', 'sort', 'start', 'count', 'include'));
            $moreUrl = $this->buildUrl('users.index', [], $input);
        } else {
            $moreUrl = '';
        }
        $document->addMeta('moreUrl', $moreUrl);

        // Finally, we can set up the discussion serializer and use it to create
        // a collection of discussion results.
        $serializer = new UserSerializer($relations);
        $document->setData($serializer->collection($results->getUsers()));

        return $this->respondWithDocument($document);
    }
}
