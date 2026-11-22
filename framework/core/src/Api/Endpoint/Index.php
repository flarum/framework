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
use Flarum\Http\RequestUtil;
use Flarum\Search\SearchCriteria;
use Flarum\Search\SearchManager;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Psr\Http\Message\ResponseInterface as Response;
use RuntimeException;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\Sourceable;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Pagination\Pagination;
use Tobyz\JsonApiServer\Resource\Paginatable;
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

                $this->rejectPagesOfUnpaginatableLists($context);

                // Reading the parameters first refuses the ones that can't be read before
                // a hook acts on the request or a pagination is built from them.
                $extracts = $this->defaultExtracts($context);

                $this->callBeforeHook($context);

                $query = $collection->query($context);

                $pagination = ($this->paginationResolver)($context);

                $filters = $this->extractFilterValue($context, $extracts);
                $sort = $this->extractSortValue($context, $extracts);
                $limit = $this->extractLimitValue($context, $extracts);

                // page[number] counts pages of the limit that applies, which a callback may change.
                if ($limit !== $extracts['limit']) {
                    $extracts['offset'] = RequestUtil::extractOffset($context->request, $limit);
                }

                $offset = $this->extractOffsetValue($context, $extracts);

                // A limit on a list that isn't paginated still makes the response a page, with
                // links to the rest; without one, the whole list has no offset to start from.
                if (! $pagination && $limit !== null && $context->collection instanceof Paginatable) {
                    $pagination = $this->paginationOfSize($context, $limit);
                } elseif (! $pagination && $limit === null) {
                    $offset = 0;
                }

                if ($pagination instanceof OffsetPagination) {
                    $pagination->offset = $offset;
                    $pagination->limit = $limit;
                    $pagination->context = $this->withoutPagePosition($pagination->context);
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

                // Counting every match can cost far more than fetching the page,
                // e.g. for a filter no index can count. Clients that don't show a
                // total can skip it with `page[total]=0`; `links.next` is then
                // worked out from the page being full.
                if (
                    $collection instanceof Countable &&
                    $this->wantsTotal($context) &&
                    ! is_null($total = $collection->count($query, $context))
                ) {
                    $meta['page']['total'] = $total;
                }

                $models = $this->callAfterHook($context, $models);

                $total ??= null;

                // A searcher fetches one result past the page, so it knows
                // whether there are more where the page being full only hints.
                $more = $context instanceof Context ? $context->getSearchResults()?->areMoreResults() : null;

                return compact('models', 'meta', 'pagination', 'total', 'more');
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

                    // Without a total, a last page that happens to be full would
                    // otherwise link to a next one, which is empty.
                    if ($total === null && ($results['more'] ?? null) === false) {
                        unset($links['next']);
                    }
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
        if ($sort) {
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

        // Sorting by a column that isn't unique leaves the order of tied rows to the
        // database, and results are paginated by offset, so an unstable order can repeat a
        // row on one page and skip it on the next. Ending every sort on the primary key
        // makes the order total. Applies with no sort too: unordered pagination has the
        // same problem.
        //
        // The direction follows the last ordering already applied, because an InnoDB
        // secondary index carries the primary key: `column DESC, id DESC` is still answered
        // by an index on `column` alone, where mixing directions forces a filesort.
        if ($query instanceof EloquentBuilder) {
            $direction = 'asc';

            foreach (array_reverse($query->getQuery()->orders ?? []) as $order) {
                if (isset($order['direction'])) {
                    $direction = $order['direction'];
                    break;
                }
            }

            $query->orderBy($query->getModel()->getQualifiedKeyName(), $direction);
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

    /**
     * Whether the request wants `meta.page.total`; `page[total]=0` (or `false`) opts out.
     */
    protected function wantsTotal(Context $context): bool
    {
        $total = $context->queryParam('page')['total'] ?? null;

        return ! in_array($total, ['0', 'false', 0, false], true);
    }

    protected function paginationOfSize(\Tobyz\JsonApiServer\Context $context, int $limit): OffsetPagination
    {
        // The links are built from the request. Without a page[limit] in them, a
        // link back to offset 0 would ask for the whole list instead of a page.
        $request = $context->request;
        $query = $request->getQueryParams();

        if (! is_array($query['page'] ?? null)) {
            $query['page'] = [];
        }

        // An empty page[limit] counts as absent too.
        if (($query['page']['limit'] ?? '') === '') {
            $query['page']['limit'] = (string) $limit;
        }

        return new OffsetPagination(
            $context->withRequest($request->withQueryParams($query)),
            $limit,
            $this->maxLimit,
        );
    }

    /**
     * The page family is reserved for pagination, and only a Paginatable resource
     * can be paginated. A request for a page of any other list can't be answered
     * as asked, so it's refused rather than given the whole list.
     */
    private function rejectPagesOfUnpaginatableLists(\Tobyz\JsonApiServer\Context $context): void
    {
        if ($context->collection instanceof Paginatable) {
            return;
        }

        $page = $context->queryParam('page');

        // page[total] only asks whether to count the list.
        $others = is_array($page)
            ? array_filter(array_diff_key($page, ['limit' => 0, 'offset' => 0, 'number' => 0, 'total' => 0]), fn ($value) => $value !== null && $value !== '')
            : [];

        if ($name = $this->pageAskedFor($context) ?? array_key_first($others)) {
            throw (new BadRequestException("page[$name] is not supported: this list is not paginated"))->setSource([
                'parameter' => "page[$name]",
            ]);
        }
    }

    /**
     * The pagination links page by offset, but page[number] and page[near] take
     * precedence over page[offset], so a link that kept either would lead back
     * to the same page.
     */
    private function withoutPagePosition(\Tobyz\JsonApiServer\Context $context): \Tobyz\JsonApiServer\Context
    {
        $request = $context->request;
        $query = $request->getQueryParams();

        if (! is_array($query['page'] ?? null) || ! array_intersect_key($query['page'], ['number' => true, 'near' => true])) {
            return $context;
        }

        unset($query['page']['number'], $query['page']['near']);

        return $context->withRequest($request->withQueryParams($query));
    }

    /**
     * A page requested from an endpoint that isn't paginated is as large as the
     * endpoint allows.
     */
    protected function defaultLimit(\Tobyz\JsonApiServer\Context $context): ?int
    {
        return $this->limit ?? ($this->pageRequested($context) ? $this->maxLimit : null);
    }

    private function pageRequested(\Tobyz\JsonApiServer\Context $context): bool
    {
        return $context->collection instanceof Paginatable && $this->pageAskedFor($context) !== null;
    }

    /**
     * The page parameter that asks for something other than the whole list from
     * its start. An empty value, page[offset]=0 and page[number]=1 don't.
     */
    private function pageAskedFor(\Tobyz\JsonApiServer\Context $context): ?string
    {
        $page = $context->queryParam('page');

        if (! is_array($page)) {
            return null;
        }

        // The value each one has at the start of the list; a limit never does.
        $start = ['limit' => null, 'offset' => 0, 'number' => 1];

        // page[number] takes precedence over page[offset] when the offset is read.
        if (! in_array($page['number'] ?? null, [null, ''], true)) {
            unset($start['offset']);
        }

        foreach ($start as $name => $startValue) {
            $value = $page[$name] ?? null;

            if ($value === null || $value === '') {
                continue;
            }

            // 0, '0' and '00' are the same offset. A value that isn't a whole
            // number asks for something, and is refused when it's read.
            $isNumber = is_int($value) || (is_string($value) && ctype_digit($value));

            if (! $isNumber || (int) $value !== $startValue) {
                return $name;
            }
        }

        return null;
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
