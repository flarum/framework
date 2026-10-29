import PaginatedListState, { type PaginatedListRequestParams } from '../states/PaginatedListState';
import type Model from '../Model';
import type { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';
import ItemList from '../utils/ItemList';
import type ResourceDefinition from './ResourceDefinition';
import type { Filter } from './types';

export interface ResourceListParams {
  sort?: string;
  q?: string;
  filter?: Record<string, string>;
}

export interface ResourceListOptions {
  /** Read and write `sort`, `q`, `filter[key]` and `page` in the URL query. Default true. */
  syncUrl?: boolean;
  pageSize?: number;
}

/**
 * The query string of the current route: after the hash for hash routes (admin), else the search.
 */
export function readQuery(): URLSearchParams {
  const hash = window.location.hash;

  return new URLSearchParams(hash.startsWith('#/') ? (hash.split('?', 2)[1] ?? '') : window.location.search);
}

/**
 * Replace the query string of the current route without adding a history entry.
 */
export function writeQuery(params: URLSearchParams): void {
  const query = params.toString();
  const { pathname, search, hash } = window.location;

  if (hash.startsWith('#/')) {
    const path = hash.split('?', 2)[0];
    window.history.replaceState(window.history.state, '', `${pathname}${search}${path}${query ? '?' + query : ''}`);
  } else {
    window.history.replaceState(window.history.state, '', `${pathname}${query ? '?' + query : ''}${hash}`);
  }
}

/**
 * Records picked by id on the current page.
 */
export interface IdsSelection {
  mode: 'ids';
  ids: Set<string>;
}

export type ResourceSelectionMode = 'ids';

/**
 * The bulk selection of a resource list.
 */
export type ResourceSelection = IdsSelection;

const FILTER_PARAM = /^filter\[(.+)\]$/;

/**
 * A paginated list of one resource type. Sorts and filters come from the definition's descriptor:
 * unknown sorts are ignored, empty filters are omitted. Pagination adapts — `hasTotal()` when the API
 * sends `meta.page.total` (numbered pages), else `hasPrev()`/`hasNext()` from the links.
 */
export default class ResourceListState<M extends Model = Model> extends PaginatedListState<M, ResourceListParams> {
  public readonly definition: ResourceDefinition<M>;
  protected syncUrl: boolean;

  /** `definition.filters()`, built once the descriptor is loaded. */
  protected filters: ItemList<Filter> | null = null;

  /** Page-scoped: any page, sort, filter or search change (and a reload) clears it. */
  public selection: ResourceSelection = { mode: 'ids', ids: new Set() };

  constructor(definition: ResourceDefinition<M>, options: ResourceListOptions = {}) {
    super({}, 1, options.pageSize ?? PaginatedListState.DEFAULT_PAGE_SIZE);

    this.definition = definition;
    this.syncUrl = options.syncUrl ?? true;
  }

  get type(): string {
    return this.definition.type;
  }

  /** Load the descriptor, adopt the URL's params (validated against it) and load the page. */
  async load(): Promise<void> {
    await this.definition.load();
    this.filters = null;

    let page = 1;

    if (this.syncUrl) {
      const query = readQuery();
      const filters = this.filterKeys();
      const filter: Record<string, string> = {};

      query.forEach((value, key) => {
        const match = key.match(FILTER_PARAM);

        if (!match || value === '') return;

        if (filters.includes(match[1])) {
          filter[match[1]] = value;
        } else if (match[1].startsWith('-') && this.isNegatable(match[1].slice(1)) && value === '1') {
          filter[match[1].slice(1)] = '0';
        }
      });

      const sort = query.get('sort') ?? '';

      this.params = this.clean({ sort: this.isSortable(sort) ? sort : '', q: query.get('q') ?? '', filter });
      page = Math.max(1, parseInt(query.get('page') ?? '1', 10) || 1);
    }

    await this.refresh(page);
  }

  sorts(): string[] {
    return this.definition.descriptor?.sorts ?? [];
  }

  /** The keys of the definition's filters, an `extend()`-added one included. */
  filterKeys(): string[] {
    return Object.keys(this.definitionFilters().toObject());
  }

  /**
   * Whether "No" (`'0'`) of this filter is sent as `filter[-key]=1`: the definition's
   * `Filter.negatable`.
   */
  isNegatable(key: string): boolean {
    const filters = this.definitionFilters();

    return filters.has(key) && !!filters.get(key).negatable;
  }

  /** `definition.filters()`, built once per descriptor load; empty until then. */
  protected definitionFilters(): ItemList<Filter> {
    if (!this.filters && this.definition.descriptor) this.filters = this.definition.filters();

    return this.filters ?? new ItemList<Filter>();
  }

  /**
   * The `filter[…]` key and value one list filter is sent (and written to the URL) as.
   */
  filterParam(key: string, value: string): [string, string] {
    return value === '0' && this.isNegatable(key) ? [`-${key}`, '1'] : [key, value];
  }

  /**
   * Remove every structured filter (the search stays).
   */
  clearFilters(): Promise<void> {
    return this.update({ ...this.params, filter: {} });
  }

  isSortable(sort: string): boolean {
    return sort !== '' && this.sorts().includes(sort.replace(/^-/, ''));
  }

  /**
   * Sort by a descriptor sort (`name` / `-name`), or `''` for the default. Others are ignored.
   */
  setSort(sort: string): Promise<void> {
    if (sort !== '' && !this.isSortable(sort)) return Promise.resolve();

    return this.update({ ...this.params, sort });
  }

  setQuery(q: string): Promise<void> {
    return this.update({ ...this.params, q });
  }

  setFilter(key: string, value: string | null | undefined): Promise<void> {
    return this.update({ ...this.params, filter: { ...this.params.filter, [key]: value ?? '' } });
  }

  changeSort(sort: string) {
    this.setSort(sort);
  }

  changeFilter(key: string, value: any) {
    this.setFilter(key, value);
  }

  getQuery(): string {
    return this.params.q ?? '';
  }

  getFilter(key: string): string {
    return this.params.filter?.[key] ?? '';
  }

  /**
   * Once a page is on screen, a new search, sort or filter keeps its rows (with `isLoading()`) until
   * the new page arrives; only the first load is the empty initial-loading state.
   */
  /**
   * Whether the type's list is paginated: its descriptor has `pagination`. An unpaginated list
   * (groups) is requested whole, without `page[limit]`/`page[offset]`, and always shows page 1.
   * True until the descriptor is loaded.
   */
  isPaginated(): boolean {
    const descriptor = this.definition.descriptor;

    return !descriptor || !!descriptor.pagination;
  }

  public refresh(page: number = 1): Promise<void> {
    if (!this.isPaginated()) page = 1;

    if (!this.pages.length) return super.refresh(page);

    this.loadingPrev = false;
    this.loadingNext = false;

    return this.goto(page);
  }

  /**
   * An empty page past the last one (e.g. a stale `?page=99`) goes to the last page when the total
   * is known.
   */
  public goto(page: number): Promise<void> {
    if (!this.isPaginated()) page = 1;

    this.clearSelection();
    this.writeUrl(page);

    return this.errorShownByRequest(
      super.goto(page).then(() => {
        const last = this.pageCount();

        if (page > 1 && last !== null && page > last && this.location.page === page && !this.getItems().length) {
          return this.goto(last);
        }

        return undefined;
      })
    );
  }

  /**
   * Reload after `removed` records of the current page were deleted: the previous page when they
   * were all of this one, else this page.
   */
  public gotoAfterRemoval(removed: number = 1): Promise<void> {
    const page = this.currentPage();

    return this.goto(removed >= this.getItems().length && page > 1 ? page - 1 : page);
  }

  hasTotal(): boolean {
    return this.totalItems !== null;
  }

  /** Number of pages when the total is known, else null. */
  pageCount(): number | null {
    if (this.totalItems === null) return null;

    return Math.max(1, Math.ceil(this.totalItems / (this.pageSize || PaginatedListState.DEFAULT_PAGE_SIZE)));
  }

  currentPage(): number {
    return this.location.page;
  }

  /** The models of the current page. */
  getItems(): M[] {
    return this.getAllItems();
  }

  isSelected(id: string): boolean {
    return this.selection.ids.has(id);
  }

  setSelected(id: string, selected: boolean): void {
    if (selected) this.selection.ids.add(id);
    else this.selection.ids.delete(id);
  }

  setPageSelected(selected: boolean): void {
    this.selection = { mode: 'ids', ids: new Set(selected ? this.getItems().map((model) => model.id()!) : []) };
  }

  /** Whether the page has records and all of them are selected. */
  isPageSelected(): boolean {
    const items = this.getItems();

    return items.length > 0 && items.every((model) => this.isSelected(model.id()!));
  }

  clearSelection(): void {
    this.selection = { mode: 'ids', ids: new Set() };
  }

  /** The selected records, in page order. */
  selectedModels(): M[] {
    const ids = this.selection.ids;

    return this.getItems().filter((model) => ids.has(model.id()!));
  }

  /**
   * The API parameters of the current sort, search and filters (no page), e.g. for an export.
   */
  listParams(): PaginatedListRequestParams {
    return this.requestParams();
  }

  /**
   * The total is the response's `meta.page.total` (0 included), or null without one. The base class
   * keeps an earlier total when the new one is 0 or missing.
   */
  protected loadPage(page: number = 1): Promise<ApiResponsePlural<M>> {
    return super.loadPage(page).then((results) => {
      const total = results.payload?.meta?.page?.total;

      this.totalItems = typeof total === 'number' ? total : null;

      return results;
    });
  }

  /** An unpaginated type gets no page parameters, so the server sends every record. */
  protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural {
    const mutated = super.mutateRequestParams(params, page);

    if (!this.isPaginated()) delete mutated.page;

    return mutated;
  }

  protected requestParams(): PaginatedListRequestParams {
    const base = this.definition.query() as PaginatedListRequestParams;
    const filter: Record<string, unknown> = { ...(base.filter as Record<string, unknown> | undefined) };

    if (this.params.q) filter.q = this.params.q;

    Object.entries(this.params.filter ?? {}).forEach(([key, value]) => {
      if (value === '' || value === null || value === undefined) return;

      const [param, sent] = this.filterParam(key, value);
      filter[param] = sent;
    });

    const params: PaginatedListRequestParams = { ...base };
    delete params.filter;

    if (this.params.sort && this.isSortable(this.params.sort)) params.sort = this.params.sort;
    if (Object.keys(filter).length) params.filter = filter;

    return params;
  }

  protected update(params: ResourceListParams): Promise<void> {
    const next = this.clean(params);

    if (JSON.stringify(next) === JSON.stringify(this.params)) {
      return Promise.resolve();
    }

    this.params = next;

    return this.refresh(1);
  }

  /** Drop empty values so the params, the request and the URL agree. */
  protected clean(params: ResourceListParams): ResourceListParams {
    const out: ResourceListParams = {};
    const filter = Object.fromEntries(Object.entries(params.filter ?? {}).filter(([, v]) => v !== '' && v !== null && v !== undefined));

    if (params.sort) out.sort = params.sort;
    if (params.q) out.q = params.q;
    if (Object.keys(filter).length) out.filter = filter;

    return out;
  }

  protected writeUrl(page: number): void {
    if (!this.syncUrl) return;

    const query = readQuery();

    [...query.keys()].filter((key) => FILTER_PARAM.test(key) || ['sort', 'q', 'page'].includes(key)).forEach((key) => query.delete(key));

    if (this.params.sort) query.set('sort', this.params.sort);
    if (this.params.q) query.set('q', this.params.q);
    Object.entries(this.params.filter ?? {}).forEach(([key, value]) => {
      const [param, sent] = this.filterParam(key, value);
      query.set(`filter[${param}]`, sent);
    });
    if (page > 1) query.set('page', String(page));

    writeQuery(query);
  }
}
