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
use Flarum\Discussion\Filter\DiscussionFilterer;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
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
     * @var DiscussionFilterer
     */
    protected $filterer;

    /**
     * @var DiscussionSearcher
     */
    protected $searcher;

    /**
     * {@inheritDoc}
     */
    protected $sort = ['lastPostedAt' => 'desc'];

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param DiscussionFilterer $filterer
     * @param DiscussionSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(DiscussionFilterer $filterer, DiscussionSearcher $searcher, UrlGenerator $url)
    {
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
        $include = array_merge($this->extractInclude($request), ['state']);

        $criteria = new SearchCriteria($actor, $filters, $sort);
        if (array_key_exists('q', $filters)) {
            $results = $this->searcher->search($criteria, $limit, $offset, $include);
        } else {
            $results = $this->filterer->filter($criteria, $limit, $offset, $include);
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        Discussion::setStateUser($actor);

        $results = $results->getResults()->load($include);

        if ($relations = array_intersect($include, ['firstPost', 'lastPost'])) {
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
