<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\DiscussionSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\DiscussionRepository;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Filter\Filterer;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListDiscussionsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = DiscussionSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'lastPostedUser',
        'mostRelevantPost',
        'mostRelevantPost.user'
    ];

    /**
     * {@inheritdoc}
     */
    public $optionalInclude = [
        'firstPost',
        'lastPost'
    ];

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['lastPostedAt', 'commentCount', 'createdAt'];

    /**
     * @var DiscussionRepository
     */
    protected $discussions;

    /**
     * @var Filterer
     */
    protected $filterer;

    /**
     * @var DiscussionSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param DiscussionRepository $discussions
     * @param Filterer $filterer
     * @param DiscussionSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(DiscussionRepository $discussions, Filterer $filterer, DiscussionSearcher $searcher, UrlGenerator $url)
    {
        $this->discussions = $discussions;
        $this->filterer = $filterer;
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $load = array_merge($this->extractInclude($request), ['state']);

        if (array_key_exists('q', $filters)) {
            $criteria = new SearchCriteria($actor, $filters['q'], $sort);

            $results = $this->searcher->search($criteria, $limit, $offset, $load);
        } else {
            $query = $this->discussions->query();

            $results = $this->filterer->filter($actor, $query, $filters, $sort, $limit, $offset, $load);
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        Discussion::setStateUser($actor);

        $results = $results->getResults()->load($load);

        if ($relations = array_intersect($load, ['firstPost', 'lastPost'])) {
            foreach ($results as $discussion) {
                foreach ($relations as $relation) {
                    if ($discussion->$relation) {
                        $discussion->$relation->discussion = $discussion;
                    }
                }
            }
        }

        return $results;
    }
}
