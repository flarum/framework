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
use Flarum\Group\Search\GroupSearcher;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Query\QueryCriteria;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListGroupsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = GroupSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $sortFields = ['nameSingular', 'namePlural', 'isHidden'];

    /**
     * {@inheritdoc}
     *
     * @var int
     */
    public $limit = -1;

    /**
     * @var GroupFilterer
     */
    protected $filterer;

    /**
     * @var GroupSearcher
     */
    protected $searcher;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param GroupFilterer $filterer
     * @param GroupSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(GroupFilterer $filterer, GroupSearcher $searcher, UrlGenerator $url)
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
        $actor = RequestUtil::getActor($request);

        $actor->assertCan('searchGroups');

        $filters = $this->extractFilter($request);
        $sort = $this->extractSort($request);
        $sortIsDefault = $this->sortIsDefault($request);

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);

        $criteria = new QueryCriteria($actor, $filters, $sort, $sortIsDefault);
        if (array_key_exists('q', $filters)) {
            $queryResults = $this->searcher->search($criteria, $limit, $offset);
        } else {
            $queryResults = $this->filterer->filter($criteria, $limit, $offset);
        }

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
