<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Filter\Filterer;
use Flarum\Http\UrlGenerator;
use Flarum\Search\SearchCriteria;
use Flarum\User\Search\UserSearcher;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
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
     * @var Filterer
     */
    protected $filterer;

    /**
     * @var UserSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param Filterer $filterer
     * @param UserSearcher $searcher
     * @param UrlGenerator $url
     * @param UserRepository $users
     */
    public function __construct(Filterer $filterer, UserSearcher $searcher, UrlGenerator $url, UserRepository $users)
    {
        $this->filterer = $filterer;
        $this->searcher = $searcher;
        $this->url = $url;
        $this->users = $users;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = $request->getAttribute('actor');

        $actor->assertCan('viewUserList');

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $load = $this->extractInclude($request);

        if (array_key_exists('q', $filters)) {
            $criteria = new SearchCriteria($actor, $filters['q'], $sort);

            $results = $this->searcher->search($criteria, $limit, $offset, $load);
        } else {
            $query = $this->users->query();

            $results = $this->filterer->filter($actor, $query, $filters, $sort, $limit, $offset, $load);
        }

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
