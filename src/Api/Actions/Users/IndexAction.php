<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Users;

use Flarum\Core\Search\SearchCriteria;
use Flarum\Core\Users\Search\UserSearcher;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Http\UrlGeneratorInterface;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var UserSearcher
     */
    protected $searcher;

    /**
     * @var UrlGeneratorInterface
     */
    protected $url;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\UserSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'groups' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = ['username', 'postsCount', 'discussionsCount', 'lastSeenTime', 'joinTime'];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param UserSearcher $searcher
     * @param UrlGeneratorInterface $url
     */
    public function __construct(UserSearcher $searcher, UrlGeneratorInterface $url)
    {
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * Get the user results, ready to be serialized and assigned to the
     * document response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $criteria = new SearchCriteria(
            $request->actor,
            $request->get('filter.q'),
            $request->sort
        );

        $results = $this->searcher->search($criteria, $request->limit, $request->offset, $request->include);

        $this->addPaginationLinks(
            $document,
            $request,
            $this->url->toRoute('flarum.api.users.index'),
            $results->areMoreResults()
        );

        return $results->getResults();
    }
}
