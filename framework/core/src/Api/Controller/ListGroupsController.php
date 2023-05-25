<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\GroupSerializer;
use Flarum\Group\Filter\GroupFilterer;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Query\QueryCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListGroupsController extends AbstractListController
{
    public ?string $serializer = GroupSerializer::class;

    public array $sortFields = ['nameSingular', 'namePlural', 'isHidden'];

    public int $limit = -1;

    public function __construct(
        protected GroupFilterer $filterer,
        protected UrlGenerator $url
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): iterable
    {
        $actor = RequestUtil::getActor($request);

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $criteria = new QueryCriteria($actor, $filters, $sort, $sortIsDefault);

        $queryResults = $this->filterer->filter($criteria, $limit, $offset);

        $document->addPaginationLinks(
            $this->url->to('api')->route('groups.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $queryResults->areMoreResults() ? null : 0
        );

        $results = $queryResults->getResults();

        $this->loadRelations($results, [], $request);

        return $results;
    }
}
