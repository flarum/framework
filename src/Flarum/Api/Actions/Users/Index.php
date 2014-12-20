<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Users\User;
use Flarum\Core\Users\UserFinder;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\UserSerializer;

class Index extends Base
{
    /**
     * The user finder.
     * 
     * @var UserFinder
     */
    protected $finder;

    /**
     * Instantiate the action.
     *
     * @param UserFinder $finder
     */
    public function __construct(UserFinder $finder)
    {
        $this->finder = $finder;
    }

    /**
     * Show a list of users.
     *
     * @todo custom rate limit for this function? determined by if $key was valid?
     * @return Response
     */
    protected function run()
    {
        $query     = $this->input('q');
        $key       = $this->input('key');
        $sort      = $this->sort(['', 'username', 'posts', 'discussions', 'lastActive', 'created']);
        $start     = $this->start();
        $count     = $this->count(50, 100);
        $include   = $this->included(['groups']);
        $relations = array_merge(['groups'], $include);

        // Set up the user finder with our search criteria, and get the
        // requested range of results with the necessary relations loaded.
        $this->finder->setUser(User::current());
        $this->finder->setQuery($query);
        $this->finder->setSort($sort['by']);
        $this->finder->setOrder($sort['order']);
        $this->finder->setKey($key);

        $users = $this->finder->results($count, $start);
        $users->load($relations);

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
            $moreUrl = $this->buildUrl('users.index', [], $input);
        } else {
            $moreUrl = '';
        }
        $this->document->addMeta('moreUrl', $moreUrl);

        // Finally, we can set up the user serializer and use it to create
        // a collection of user results.
        $serializer = new UserSerializer($relations);
        $this->document->setPrimaryElement($serializer->collection($users));

        return $this->respondWithDocument();
    }
}
