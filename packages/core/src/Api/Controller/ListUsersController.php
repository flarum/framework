<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\Search\UserSearcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListUsersController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = UserSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = ['groups'];

    /**
     * {@inheritdoc}
     */
    public $sortFields = [
        'username',
        'commentCount',
        'discussionCount',
        'lastSeenAt',
        'joinedAt'
    ];

    /**
     * @var UserSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param UserSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(UserSearcher $searcher, UrlGenerator $url)
    {
        $this->searcher = $searcher;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        if ($actor->cannot('viewUserList')) {
            throw new PermissionDeniedException;
        }

        $query = array_get($this->extractFilter($request), 'q');
        $sort = $this->extractSort($request);

        $criteria = new SearchCriteria($actor, $query, $sort);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $load = $this->extractInclude($request);

        $results = $this->searcher->search($criteria, $limit, $offset, $load);

        $document->addPaginationLinks(
            $this->url->to('api')->route('users.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        return $results->getResults();
    }
}
