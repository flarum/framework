<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Search\Users\UserSearchCriteria;
use Flarum\Core\Search\Users\UserSearcher;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Http\UrlGeneratorInterface;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * The user searcher.
     *
     * @var \Flarum\Core\Search\Users\UserSearcher
     */
    protected $searcher;

    /**
     * The URL generator.
     *
     * @var \Flarum\Http\UrlGeneratorInterface
     */
    protected $url;

    /**
     * Instantiate the action.
     *
     * @param  \Flarum\Core\Search\Users\UserSearcher  $searcher
     * @param  \Flarum\Http\UrlGeneratorInterface  $url
     */
    public function __construct(UserSearcher $searcher, UrlGeneratorInterface $url)
    {
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'groups' => true
    ];

    /**
     * The fields that are available to be sorted by.
     *
     * @var array
     */
    public static $sortFields = ['username', 'postsCount', 'discussionsCount', 'lastSeenTime', 'joinTime'];

    /**
     * Get the user results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $criteria = new UserSearchCriteria(
            $request->actor->getUser(),
            $request->get('q'),
            $request->sort
        );

        $results = $this->searcher->search($criteria, $request->limit, $request->offset, $request->include);

        if (($total = $results->getTotal()) !== null) {
            $document->addMeta('total', $total);
        }

        static::addPaginationLinks(
            $document,
            $request,
            $this->url->toRoute('flarum.api.users.index'),
            $total ?: $results->areMoreResults()
        );

        return $results->getUsers();
    }
}
