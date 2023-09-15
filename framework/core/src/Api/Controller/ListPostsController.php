<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\PostSerializer;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Post\Filter\PostFilterer;
use Flarum\Post\PostRepository;
use Flarum\Query\QueryCriteria;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;
use Tobscure\JsonApi\Exception\InvalidParameterException;

class ListPostsController extends AbstractListController
{
    public ?string $serializer = PostSerializer::class;

    public array $include = [
        'user',
        'user.groups',
        'editedUser',
        'hiddenUser',
        'discussion'
    ];

    public array $sortFields = ['number', 'createdAt'];

    public function __construct(
        protected PostFilterer $filterer,
        protected PostRepository $posts,
        protected UrlGenerator $url
    ) {
    }

    protected function data(Request $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $results = $this->filterer->filter(new QueryCriteria($actor, $filters, $sort, $sortIsDefault), $limit, $offset);

        $document->addPaginationLinks(
            $this->url->route('api.posts.index'),
            $request->query(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        // Eager load discussion for use in the policies,
        // eager loading does not affect the JSON response,
        // the response only includes relations included in the request.
        if (! in_array('discussion', $include)) {
            $include[] = 'discussion';
        }

        if (in_array('user', $include)) {
            $include[] = 'user.groups';
        }

        $results = $results->getResults();

        $this->loadRelations($results, $include, $request);

        return $results;
    }

    /**
     * @link https://github.com/flarum/framework/pull/3506
     */
    protected function extractSort(Request $request): ?array
    {
        $sort = [];

        foreach ((parent::extractSort($request) ?: []) as $field => $direction) {
            $sort["posts.$field"] = $direction;
        }

        return $sort;
    }

    protected function extractOffset(Request $request): int
    {
        $actor = RequestUtil::getActor($request);
        $sort = $this->extractSort($request);
        $limit = $this->extractLimit($request);
        $filter = $this->extractFilter($request);

        if (($near = $request->query('page.near')) > 1) {
            if (count($filter) > 1 || ! isset($filter['discussion']) || $sort) {
                throw new InvalidParameterException(
                    'You can only use page[near] with filter[discussion] and the default sort order'
                );
            }

            $offset = $this->posts->getIndexForNumber((int) $filter['discussion'], $near, $actor);

            return max(0, $offset - $limit / 2);
        }

        return parent::extractOffset($request);
    }
}
