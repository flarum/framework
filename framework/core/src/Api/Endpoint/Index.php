<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint;

use Closure;
use Flarum\Api\Context;
use Flarum\Api\Endpoint\Concerns\ExtractsListingParams;
use Flarum\Api\Endpoint\Concerns\HasAuthorization;
use Flarum\Api\Endpoint\Concerns\HasCustomHooks;
use Flarum\Api\Endpoint\Concerns\IncludesData;
use Flarum\Api\Resource\AbstractDatabaseResource;
use Flarum\Api\Resource\AbstractResource;
use Flarum\Api\Resource\Contracts\Countable;
use Flarum\Api\Resource\Contracts\Listable;
use Flarum\Api\Serializer;
use Flarum\Database\Eloquent\Collection;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\Sourceable;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Pagination\Pagination;
use Tobyz\JsonApiServer\Schema\Concerns\HasMeta;

use function Tobyz\JsonApiServer\json_api_response;

class Index extends Endpoint
{
    use HasMeta;
    use IncludesData;
    use HasAuthorization;
    use ExtractsListingParams;
    use HasCustomHooks;

    public Closure $paginationResolver;
    protected ?Closure $query = null;

    public function __construct(string $name)
    {
        parent::__construct($name);

        $this->paginationResolver = fn () => null;
    }

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'index');
    }

    public function query(?Closure $query): static
    {
        $this->query = $query;

        return $this;
    }

    protected function setUp(): void
    {
        $this->route('GET', '/')
            ->query(function ($query, ?Pagination $pagination, Context $context, array $filters, ?array $sort, int $offset, ?int $limit): Context {
                $collection = $context->collection;

                // This model has a searcher API, so we'll use that instead of the default.
                // The searcher API allows swapping the default search engine for a custom one.
                /** @var SearchManager $search */
                $search = $context->api->getContainer()->make(SearchManager::class);
                $modelClass = $collection instanceof AbstractDatabaseResource ? $collection->model() : null;

                if ($query instanceof Builder && $search->searchable($modelClass)) {
                    $actor = $context->getActor();

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

                    $this->applySorts($query, $context, $sort);
                    $this->applyFilters($query, $context, $filters);

                    if ($pagination && method_exists($pagination, 'apply')) {
                        $pagination->apply($query);
                    }
                }

                return $context;
            })
            ->action(function (\Tobyz\JsonApiServer\Context $context) {
                if (str_contains($context->path(), '/')) {
                    return null;
                }

                $collection = $context->collection;

                if (! $collection instanceof Listable) {
                    throw new RuntimeException(
                        sprintf('%s must implement %s', get_class($collection), Listable::class),
                    );
                }

                $this->callBeforeHook($context);

                $query = $collection->query($context);

                $pagination = ($this->paginationResolver)($context);

                $extracts = $this->defaultExtracts($context);

                $filters = $this->extractFilterValue($context, $extracts);
                $sort = $this->extractSortValue($context, $extracts);
                $limit = $this->extractLimitValue($context, $extracts);
                $offset = $this->extractOffsetValue($context, $extracts);

                if ($pagination instanceof OffsetPagination) {
                    $pagination->offset = $offset;
                    $pagination->limit = $limit;
                }

                if ($this->query) {
                    $context = ($this->query)($query, $pagination, $context, $filters, $sort, $offset, $limit);

                    if (! $context instanceof Context) {
                        throw new RuntimeException('The Index endpoint query closure must return a Context instance.');
                    }
                } else {
                    /** @var Context $context */
                    $context = $context->withQuery($query);

                    $this->applySorts($query, $context, $sort);
                    $this->applyFilters($query, $context, $filters);

                    if ($pagination) {
                        $pagination->apply($query);
                    }
                }

                $meta = $this->serializeMeta($context);

                $models = $collection->results($query, $context);

                if (
                    $collection instanceof Countable &&
                    ! is_null($total = $collection->count($query, $context))
                ) {
                    $meta['page']['total'] = $total;
                }

                $models = $this->callAfterHook($context, $models);

                $total ??= null;

                return compact('models', 'meta', 'pagination', 'total');
            })
            ->beforeSerialization(function (Context $context, array $results) {
                // @phpstan-ignore-next-line
                $this->loadRelations(Collection::make($results['models']), $context, $this->getInclude($context));
            })
            ->response(function (Context $context, array $results): Response {
                $collection = $context->collection;

                ['models' => $models, 'meta' => $meta, 'pagination' => $pagination, 'total' => $total] = $results;

                $serializer = new Serializer($context);

                $include = $this->getInclude($context);

                foreach ($models as $model) {
                    $serializer->addPrimary(
                        $context->resource($collection->resource($model, $context)),
                        $model,
                        $include,
                    );
                }

                [$data, $included] = $serializer->serialize();

                $links = [];

                if ($pagination) {
                    $meta['page'] = array_merge($meta['page'] ?? [], $pagination->meta());
                    $links = array_merge($links, $pagination->links(count($data), $total));
                }

                return json_api_response(compact('data', 'included', 'meta', 'links'));
            });
    }

    public function defaultSort(?string $defaultSort): static
    {
        $this->defaultSort = $defaultSort;

        return $this;
    }

    final protected function applySorts($query, Context $context, ?array $sort): void
    {
        if (! $sort) {
            return;
        }

        $collection = $context->collection;

        if (! $collection instanceof AbstractResource) {
            throw new RuntimeException('The collection '.$collection::class.' must extend '.AbstractResource::class);
        }

        $sorts = $collection->resolveSorts();

        foreach ($sort as $name => $direction) {
            foreach ($sorts as $field) {
                if ($field->name === $name && $field->isVisible($context)) {
                    $field->apply($query, $direction, $context);
                    continue 2;
                }
            }

            throw (new BadRequestException("Invalid sort: $name"))->setSource([
                'parameter' => 'sort',
            ]);
        }
    }

    final protected function applyFilters($query, Context $context, array $filters): void
    {
        if (empty($filters)) {
            return;
        }

        $collection = $context->collection;

        if (! $collection instanceof Listable) {
            throw new RuntimeException(
                sprintf('%s must implement %s', $collection::class, \Tobyz\JsonApiServer\Resource\Listable::class),
            );
        }

        try {
            $context = $context->withCollection($collection);
            $availableFilters = $collection->filters();

            foreach ($filters as $name => $value) {
                foreach ($availableFilters as $filter) {
                    if ($filter->name === $name && $filter->isVisible($context)) {
                        $filter->apply($query, $value, $context);
                        continue 2;
                    }
                }

                throw (new BadRequestException("Invalid filter: $name"))->setSource([
                    'parameter' => "[$name]",
                ]);
            }
        } catch (Sourceable $e) {
            throw $e->prependSource(['parameter' => 'filter']);
        }
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
