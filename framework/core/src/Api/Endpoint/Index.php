<?php

namespace Flarum\Api\Endpoint;

use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\HasCustomRoute;
use Flarum\Api\Endpoint\Concerns\HasEagerLoading;
use Flarum\Http\RequestUtil;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Index as BaseIndex;
use Tobyz\JsonApiServer\Exception\ForbiddenException;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Resource\Countable;
use Tobyz\JsonApiServer\Resource\Listable;
use Tobyz\JsonApiServer\Serializer;
use function Tobyz\JsonApiServer\json_api_response;

class Index extends BaseIndex implements EndpointInterface
{
    use HasAuthorization;
    use HasEagerLoading;
    use ExtractsListingParams;
    use HasCustomHooks;

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

    /** {@inheritDoc} */
    public function handle(Context $context): ?Response
    {
        $collection = $context->collection;

        if (!$collection instanceof Listable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', get_class($collection), Listable::class),
            );
        }

        if (!$this->isVisible($context)) {
            throw new ForbiddenException();
        }

        $this->callBeforeHook($context);

        $pagination = ($this->paginationResolver)($context);

        $query = $collection->query($context);

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

        $meta = $this->serializeMeta($context);
        $links = [];

        if (
            $collection instanceof Countable &&
            !is_null($total = $collection->count($query, $context))
        ) {
            $meta['page']['total'] = $total;
        }

        $models = $collection->results($query, $context);

        $models = $this->callAfterHook($context, $models);

        $include = $this->getInclude($context);

        $this->loadRelations($models, $context, $include);

        $serializer = new Serializer($context);

        foreach ($models as $model) {
            $serializer->addPrimary(
                $context->resource($collection->resource($model, $context)),
                $model,
                $include,
            );
        }

        [$data, $included] = $serializer->serialize();

        if ($pagination) {
            $meta['page'] = array_merge($meta['page'] ?? [], $pagination->meta());
            $links = array_merge($links, $pagination->links(count($data), $total ?? null));
        }

        return json_api_response(compact('data', 'included', 'meta', 'links'));
    }
}
