<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Post\PostRepository;
use Flarum\Post\Search\PostSearcher;
use Flarum\Search\SearchCriteria;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ListPostsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = PostSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'user',
        'user.groups',
        'editedUser',
        'hiddenUser',
        'discussion'
    ];

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['createdAt'];

    /**
     * @var \Flarum\Post\PostRepository
     */
    protected $posts;

    /**
     * @var PostSearcher
     */
    protected $searcher;

    /**
     * @param \Flarum\Post\PostRepository $posts
     */
    public function __construct(PostRepository $posts, PostSearcher $searcher)
    {
        $this->posts = $posts;
        $this->searcher = $searcher;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');
        $query = Arr::get($this->extractFilter($request), 'q');
        $sort = $this->extractSort($request);
        $load = $this->extractInclude($request);

        if ($postIds = Arr::get($this->extractFilter($request), 'id')) {
            $postIds = explode(',', $postIds);
            return $this->posts->findByIds($postIds, $actor)->load($load);
        }

        $criteria = new SearchCriteria($actor, $query, $sort);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $results = $this->searcher->search($criteria, $limit, $offset, $load);

        return $results->getResults();
    }

    /**
     * {@inheritdoc}
     */
    protected function extractOffset(ServerRequestInterface $request)
    {
        $actor = $request->getAttribute('actor');
        $queryParams = $request->getQueryParams();
        $sort = $this->extractSort($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        if (($near = Arr::get($queryParams, 'page.near')) > 1) {
            if (count($filter) > 1 || ! isset($filter['discussion']) || $sort) {
                throw new InvalidParameterException(
                    'You can only use page[near] with filter[discussion] and the default sort order'
                );
            }

            $offset = $this->posts->getIndexForNumber($filter['discussion'], $near, $actor);

            return max(0, $offset - $limit / 2);
        }

        return parent::extractOffset($request);
    }
}
