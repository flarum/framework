<?php namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Discussions\Discussion;
use Flarum\Core\Discussions\DiscussionFinder;
use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\DiscussionSerializer;

class Index extends Base
{
    /**
     * The discussion finder.
     * 
     * @var DiscussionFinder
     */
    protected $finder;

    /**
     * Instantiate the action.
     *
     * @param DiscussionFinder $finder
     */
    public function __construct(DiscussionFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Show a list of discussions.
     *
     * @todo custom rate limit for this function? determined by if $key was valid?
     * @return Response
     */
    protected function run()
    {
        $query   = $this->input('q');
        $key     = $this->input('key');
        $start   = $this->start();
        $include = $this->included(['startPost', 'lastPost', 'relevantPosts']);
        $count   = $this->count($include ? 20 : 50, 50);
        $sort    = $this->sort(['', 'lastPost', 'replies', 'created']);

        $relations = array_merge(['startUser', 'lastUser'], $include);

        // Set up the discussion finder with our search criteria, and get the
        // requested range of results with the necessary relations loaded.
        $this->finder->setUser(User::current());
        $this->finder->setQuery($query);
        $this->finder->setSort($sort['by']);
        $this->finder->setOrder($sort['order']);
        $this->finder->setKey($key);

        $discussions = $this->finder->results($count, $start, array_merge($relations, ['state']));

        if (($total = $this->finder->getCount()) !== null) {
            $this->document->addMeta('total', $total);
        }
        if (($key = $this->finder->getKey()) !== null) {
            $this->document->addMeta('key', $key);
        }

        // If there are more results, then we need to construct a URL to the
        // next results page and add that to the metadata. We do this by
        // compacting all of the valid query parameters which have been
        // specified.
        if ($this->finder->areMoreResults()) {
            $start += $count;
            $include = implode(',', $include);
            $sort = $sort['string'];
            $input = array_filter(compact('query', 'key', 'sort', 'start', 'count', 'include'));
            $moreUrl = $this->buildUrl('discussions.index', [], $input);
        } else {
            $moreUrl = '';
        }
        $this->document->addMeta('moreUrl', $moreUrl);

        // Finally, we can set up the discussion serializer and use it to create
        // a collection of discussion results.
        $serializer = new DiscussionSerializer($relations);
        $this->document->setPrimaryElement($serializer->collection($discussions));

        return $this->respondWithDocument();
    }
}
