import PaginatedListState, { type PaginatedListRequestParams } from '../states/PaginatedListState';
import type Model from '../Model';
import type { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';
import ItemList from '../utils/ItemList';
import type ResourceDefinition from './ResourceDefinition';
import type { Filter, ResourceDescriptor } from './types';
import ViewPreferences, { type SavedView } from './ViewPreferences';

export interface ResourceListParams {
  sort?: string;
  q?: string;
  filter?: Record<string, string>;
}

export interface ResourceListOptions {
  /** Read and write `sort`, `q`, `filter[key]` and `page` in the URL query. Default true. */
  syncUrl?: boolean;
  /** Save the filters and sort per type (`ViewPreferences`) and open with them. Default `syncUrl`. */
  persist?: boolean;
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
 * The bulk selection of a resource list: records picked by id on the current page.
 */
export interface ResourceSelection {
  ids: Set<string>;
}

const FILTER_PARAM = /^filter\[(.+)\]$/;

/**
 * A paginated list of one resource type; sorts and filters come from the definition's descriptor.
 *
 * The list opens with the URL's filters and sort when it names any, else with the saved view
 * (`ViewPreferences`). A filter absent from both takes its `default`; a cleared one is kept as `''`
 * in the URL and the saved view, so its default does not come back. Opening a link does not
 * overwrite the saved view until the user changes something. The search and the page come from the
 * URL only.
 */
export default class ResourceListState<M extends Model = Model> extends PaginatedListState<M, ResourceListParams> {
  public readonly definition: ResourceDefinition<M>;
  protected syncUrl: boolean;
  protected persist: boolean;

  /** `definition.filters()` and the descriptor it was built for. */
  protected filterItems: ItemList<Filter> | null = null;
  protected filtersDescriptor: ResourceDescriptor | null = null;

  /** Bumped by each `goto()`; a response to an older one is dropped. */
  protected sequence = 0;
  /** The page size and total of the latest applied response. */
  protected applied: { pageSize: number | null; totalItems: number | null } = { pageSize: null, totalItems: null };

  /** Page-scoped: any page, sort, filter or search change (and a reload) clears it. */
  public selection: ResourceSelection = { ids: new Set() };

  constructor(definition: ResourceDefinition<M>, options: ResourceListOptions = {}) {
    super({}, 1, options.pageSize ?? PaginatedListState.DEFAULT_PAGE_SIZE);

    this.definition = definition;
    this.syncUrl = options.syncUrl ?? true;
    this.persist = options.persist ?? this.syncUrl;
  }

  get type(): string {
    return this.definition.type;
  }

  /** Adopts the initial view (see the class), then loads the URL's page. */
  async load(): Promise<void> {
    await this.definition.load();

    const query = this.syncUrl ? readQuery() : new URLSearchParams();
    const url = this.urlView(query);
    const saved = this.savedView();
    const view = url ?? saved ?? {};
    const defaults = this.defaultView();
    const filter = { ...view.filter };
    const page = Math.max(1, parseInt(query.get('page') ?? '1', 10) || 1);

    Object.entries(defaults.filter ?? {}).forEach(([key, value]) => {
      if (!(key in (url?.filter ?? {})) && !(key in (saved?.filter ?? {}))) filter[key] = value;
    });

    const sort = view.sort || defaults.sort;

    this.params = this.clean({
      sort: sort && this.isSortable(sort) ? sort : '',
      q: query.get('q') ?? '',
      filter: this.withRequired(filter),
    });

    await this.refresh(page);
  }

  /** The URL's filters (`''` for a cleared one) and sort, null when it names neither. */
  urlView(query: URLSearchParams): SavedView | null {
    const filters = this.filterKeys();
    const filter: Record<string, string> = {};
    let explicit = query.has('sort');

    query.forEach((value, key) => {
      const match = key.match(FILTER_PARAM);

      if (!match) return;

      explicit = true;

      if (filters.includes(match[1])) {
        filter[match[1]] ??= value;
      } else if (match[1].startsWith('-') && this.isNegatable(match[1].slice(1))) {
        filter[match[1].slice(1)] = value === '1' ? '0' : '';
      }
    });

    return explicit ? { sort: query.get('sort') ?? '', filter } : null;
  }

  /**
   * The saved view, without unknown filters and enum values the descriptor no longer offers; null
   * when there is none or it is not used.
   */
  savedView(): SavedView | null {
    const saved = this.persist ? ViewPreferences.load(this.type) : null;

    if (!saved) return null;

    const filters = this.filterKeys();
    const filter: Record<string, string> = {};

    Object.entries(saved.filter ?? {}).forEach(([key, value]) => {
      if (!filters.includes(key)) return;

      const allowed = this.definition.descriptor?.filters?.find((f) => f.key === key)?.enum;

      if (allowed && value !== '') {
        value = value
          .split(',')
          .filter((part) => allowed.includes(part))
          .join(',');

        if (value === '') return;
      }

      filter[key] = value;
    });

    return { sort: saved.sort, filter };
  }

  /** The filters' `default`s; a subclass may add a default `sort`. */
  defaultView(): SavedView {
    const filter: Record<string, string> = {};

    Object.entries(this.filters().toObject()).forEach(([key, item]) => {
      if (item.content.default) filter[key] = item.content.default;
    });

    return { filter };
  }

  /** `filter` plus `''` for each filter with a default that it leaves out, so clearing one sticks. */
  protected withClearedDefaults(filter: Record<string, string> = {}): Record<string, string> {
    const out = { ...filter };

    Object.entries(this.filters().toObject()).forEach(([key, item]) => {
      if (item.content.default && !out[key]) out[key] = '';
    });

    return out;
  }

  /** The definition's `Filter.required`. */
  isRequired(key: string): boolean {
    const filters = this.filters();

    return filters.has(key) && !!filters.get(key).required;
  }

  /**
   * `filter` with every empty required filter set back: to `previous`'s value, else the saved
   * view's, else its default.
   */
  protected withRequired(filter: Record<string, string>, previous: Record<string, string> = {}): Record<string, string> {
    const out = { ...filter };
    let saved: SavedView | null | undefined;

    Object.entries(this.filters().toObject()).forEach(([key, item]) => {
      if (!item.content.required || (out[key] ?? '') !== '') return;

      if (previous[key]) {
        out[key] = previous[key];
        return;
      }

      saved ??= this.persist ? ViewPreferences.load(this.type) : null;
      out[key] = saved?.filter?.[key] || item.content.default || '';
    });

    return out;
  }

  sorts(): string[] {
    return this.definition.descriptor?.sorts ?? [];
  }

  /** The keys of the definition's filters, an `extend()`-added one included. */
  filterKeys(): string[] {
    return Object.keys(this.filters().toObject());
  }

  /**
   * Whether "No" (`'0'`) of this filter is sent as `filter[-key]=1`: the definition's
   * `Filter.negatable`.
   */
  isNegatable(key: string): boolean {
    const filters = this.filters();

    return filters.has(key) && !!filters.get(key).negatable;
  }

  /**
   * `definition.filters()`, built once per descriptor and shared with the table's filter bar, so
   * both read the same list. Empty until the descriptor is loaded.
   */
  filters(): ItemList<Filter> {
    const descriptor = this.definition.descriptor;

    if (descriptor && descriptor !== this.filtersDescriptor) {
      this.filterItems = this.definition.filters();
      this.filtersDescriptor = descriptor;
    }

    return this.filterItems ?? new ItemList<Filter>();
  }

  /**
   * The `filter[…]` key and value one list filter is sent (and written to the URL) as.
   */
  filterParam(key: string, value: string): [string, string] {
    return value === '0' && this.isNegatable(key) ? [`-${key}`, '1'] : [key, value];
  }

  /**
   * Remove every structured filter but the required ones (the search stays).
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
   * is known. Only the latest call's response is applied; an earlier one resolving later is dropped.
   */
  public goto(page: number): Promise<void> {
    if (!this.isPaginated()) page = 1;

    const sequence = ++this.sequence;

    this.clearSelection();
    this.writeUrl(page);
    this.location = { page };

    if (!this.initialLoading) this.loadingPage = true;

    return this.errorShownByRequest(
      this.loadPage(page)
        .then((results) => {
          if (sequence !== this.sequence) return undefined;

          this.pages = [];
          this.parseResults(page, results);

          const last = this.pageCount();

          if (page > 1 && last !== null && page > last && !this.getItems().length) return this.goto(last);

          return undefined;
        })
        .finally(() => {
          if (sequence !== this.sequence) return;

          this.initialLoading = false;
          this.loadingPage = false;
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

  /** Show `size` records per page, from the first page. A no-op for an unpaginated type. */
  setPageSize(size: number): Promise<void> {
    if (!this.isPaginated() || size === this.pageSize) return Promise.resolve();

    this.pageSize = size;

    return this.goto(1);
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

  /** Select every record of the page that `selectable` accepts (default: all), or none. */
  setPageSelected(selected: boolean, selectable: (model: M) => boolean = () => true): void {
    this.selection = {
      ids: new Set(
        selected
          ? this.getItems()
              .filter(selectable)
              .map((model) => model.id()!)
          : []
      ),
    };
  }

  /** Whether the page has records `selectable` accepts (default: all) and all of them are selected. */
  isPageSelected(selectable: (model: M) => boolean = () => true): boolean {
    const items = this.getItems().filter(selectable);

    return items.length > 0 && items.every((model) => this.isSelected(model.id()!));
  }

  clearSelection(): void {
    this.selection = { ids: new Set() };
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
   * The total is the response's `meta.page.total` (0 included), or null without one. A response
   * superseded by a later `goto()` puts back the page size and total the base class took from it.
   */
  protected loadPage(page: number = 1): Promise<ApiResponsePlural<M>> {
    const sequence = this.sequence;

    return super.loadPage(page).then((results) => {
      if (sequence !== this.sequence) {
        this.pageSize = this.applied.pageSize;
        this.totalItems = this.applied.totalItems;

        return results;
      }

      const total = results.payload?.meta?.page?.total;

      this.totalItems = typeof total === 'number' ? total : null;
      this.applied = { pageSize: this.pageSize, totalItems: this.totalItems };

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

  /** Adopt changed params, save the view and reload from page 1. Required filters stay set. */
  protected update(params: ResourceListParams): Promise<void> {
    const next = this.clean({ ...params, filter: this.withRequired(params.filter ?? {}, this.params.filter) });

    if (JSON.stringify(next) === JSON.stringify(this.params)) {
      return Promise.resolve();
    }

    this.params = next;

    if (this.persist) ViewPreferences.save(this.type, { sort: next.sort, filter: this.withClearedDefaults(next.filter) });

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
    Object.entries(this.withClearedDefaults(this.params.filter)).forEach(([key, value]) => {
      const [param, sent] = value === '' ? [key, ''] : this.filterParam(key, value);
      query.set(`filter[${param}]`, sent);
    });
    if (page > 1) query.set('page', String(page));

    writeQuery(query);
  }
}
