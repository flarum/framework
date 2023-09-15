<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\UserSerializer;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Query\QueryCriteria;
use Flarum\User\Filter\UserFilterer;
use Flarum\User\Search\UserSearcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListUsersController extends AbstractListController
{
    public ?string $serializer = UserSerializer::class;

    public array $include = ['groups'];

    public array $sortFields = [
        'username',
        'commentCount',
        'discussionCount',
        'lastSeenAt',
        'joinedAt'
    ];

    public function __construct(
        protected UserFilterer $filterer,
        protected UserSearcher $searcher,
        protected UrlGenerator $url
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertCan('searchUsers');

        if (! $actor->hasPermission('user.viewLastSeenAt')) {
            // If a user cannot see everyone's last online date, we prevent them from sorting by it
            // Otherwise this sort field would defeat the privacy setting discloseOnline
            // We use remove instead of add so that extensions can still completely disable the sort using the extender
            $this->removeSortField('lastSeenAt');
        }

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        $criteria = new QueryCriteria($actor, $filters, $sort, $sortIsDefault);
        if (array_key_exists('q', $filters)) {
            $results = $this->searcher->search($criteria, $limit, $offset);
        } else {
            $results = $this->filterer->filter($criteria, $limit, $offset);
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('users.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        $results = $results->getResults();

        $this->loadRelations($results, $include, $request);

        return $results;
    }
}
