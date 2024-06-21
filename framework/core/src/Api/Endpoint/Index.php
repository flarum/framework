<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Tobyz\JsonApiServer\Endpoint\Index as BaseIndex;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Pagination\Pagination;

class Index extends BaseIndex implements EndpointInterface
{
    use HasAuthorization;
    use ExtractsListingParams;
    use HasCustomHooks;

    public function setUp(): void
    {
        parent::setUp();

        $this
            ->query(function ($query, ?Pagination $pagination, Context $context): Context {
                // This model has a searcher API, so we'll use that instead of the default.
                // The searcher API allows swapping the default search engine for a custom one.
                $search = $context->api->getContainer()->make(SearchManager::class);
                $modelClass = $query->getModel()::class;

                if ($query instanceof Builder && $search->searchable($modelClass)) {
                    $actor = $context->getActor();

                    $extracts = $this->defaultExtracts($context);

                    $filters = $this->extractFilterValue($context, $extracts);
                    $sort = $this->extractSortValue($context, $extracts);
                    $limit = $this->extractLimitValue($context, $extracts);
                    $offset = $this->extractOffsetValue($context, $extracts);

                    $sortIsDefault = ! $context->queryParam('sort');

                    $results = $search->query(
                        $modelClass,
                        new SearchCriteria($actor, $filters, $limit, $offset, $sort, $sortIsDefault),
                    );

                    $context = $context->withSearchResults($results);
                }
                // If the model doesn't have a searcher API, we'll just use the default logic.
                else {
                    $context = $context->withQuery($query);

                    $this->applySorts($query, $context);
                    $this->applyFilters($query, $context);

                    $pagination?->apply($query);
                }

                return $context;
            });
    }

    public function paginate(int $defaultLimit = 20, int $maxLimit = 50): static
    {
        $this->limit = $defaultLimit;
        $this->maxLimit = $maxLimit;

        $this->paginationResolver = fn (Context $context) => new OffsetPagination(
            $context,
            $this->limit,
            $this->maxLimit,
        );

        return $this;
    }
}
