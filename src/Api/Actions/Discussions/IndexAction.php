<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Discussions;

use Flarum\Core\Search\SearchCriteria;
use Flarum\Core\Discussions\Search\DiscussionSearcher;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Http\UrlGeneratorInterface;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var DiscussionSearcher
     */
    protected $searcher;

    /**
     * @var UrlGeneratorInterface
     */
    protected $url;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\DiscussionSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'startUser' => true,
        'lastUser' => true,
        'startPost' => false,
        'lastPost' => false,
        'relevantPosts' => true,
        'relevantPosts.discussion' => true,
        'relevantPosts.user' => true
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
    public $sortFields = ['lastTime', 'commentsCount', 'startTime'];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param DiscussionSearcher $searcher
     * @param UrlGeneratorInterface $url
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

        $load = array_merge($request->include, ['state']);

        $results = $this->searcher->search($criteria, $request->limit, $request->offset, $load);

        // TODO: add query params (filter, sort, include) to the pagination URLs
        $this->addPaginationLinks(
            $document,
            $request,
            $request->http ? $this->url->toRoute('flarum.api.discussions.index') : '',
            $results->areMoreResults()
        );

        return $results->getResults();
    }
}
