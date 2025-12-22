import app from '../../common/app';
import type Model from '../Model';
import type { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';
import type Mithril from 'mithril';

export type SortMapItem =
  | string
  | {
      sort: string;
      label: Mithril.Children;
    };

export type SortMap = {
  [key: string]: SortMapItem;
};

export interface Page<TModel extends Model> {
  number: number;
  items: ApiResponsePlural<TModel> | TModel[];

  hasPrev?: boolean;
  hasNext?: boolean;
}

export interface PaginationLocation {
  page: number;
  startIndex?: number;
  endIndex?: number;
}

export interface PaginatedListParams {
  [key: string]: any;
}

export interface PaginatedListRequestParams extends Omit<ApiQueryParamsPlural, 'include'> {
  include?: string | string[];
}

export default abstract class PaginatedListState<T extends Model, P extends PaginatedListParams = PaginatedListParams> {
  /**
   * This value should not be relied upon when preloading an API document.
   * In those cases the pageSize should be taken from the meta information of the preloaded
   * document. Checkout `DiscussionListState.loadPage` for an example.
   */
  public static DEFAULT_PAGE_SIZE = 20;

  protected location!: PaginationLocation;
  public pageSize: number | null;
  public totalItems: number | null = null;

  protected pages: Page<T>[] = [];
  protected params: P = {} as P;

  protected initialLoading: boolean = false;
  protected loadingPrev: boolean = false;
  protected loadingNext: boolean = false;
  protected loadingPage: boolean = false;

  protected constructor(params: P = {} as P, page: number = 1, pageSize: number | null = null) {
    this.params = params;

    this.location = { page };
    this.pageSize = pageSize;
  }

  abstract get type(): string;

  public clear(): void {
    this.pages = [];

    m.redraw();
  }

  public loadPrev(): Promise<void> {
    if (this.loadingPrev || !this.hasPrev()) return Promise.resolve();

    this.loadingPrev = true;

    const page: number = this.getPrevPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingPrev = false));
  }

  public loadNext(): Promise<void> {
    if (this.loadingNext) return Promise.resolve();

    this.loadingNext = true;

    const page: number = this.getNextPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingNext = false));
  }

  protected parseResults(pg: number, results: ApiResponsePlural<T>): void {
    const pageNum = Number(pg);

    const links = results.payload?.links;
    const page = {
      number: pageNum,
      items: results,
      hasNext: !!links?.next,
      hasPrev: !!links?.prev,
    };

    if (this.isEmpty() || pageNum > this.getNextPageNumber() - 1) {
      this.pages.push(page);
    } else {
      this.pages.unshift(page);
    }

    this.location = { page: pageNum };

    m.redraw();
  }

  /**
   * Load a new page of results.
   */
  protected loadPage(page = 1): Promise<ApiResponsePlural<T>> {
    const reqParams = this.requestParams();

    const include = Array.isArray(reqParams.include) ? reqParams.include.join(',') : reqParams.include;

    const params: ApiQueryParamsPlural = {
      ...reqParams,
      page: {
        ...reqParams.page,
        offset: (this.pageSize && this.pageSize * (page - 1)) || 0,
        limit: this.pageSize || undefined,
      },
      include,
    };

    if (typeof params.include === 'undefined') {
      delete params.include;
    }

    return app.store.find<T[]>(this.type, this.mutateRequestParams(params, page)).then((results) => {
      const usedPerPage = results.payload?.meta?.perPage;
      const usedTotal = results.payload?.meta?.page?.total;

      /*
       * If this state does not rely on a preloaded API document to know the page size,
       * then there is no initial list, and therefore the page size can be taken from subsequent requests.
       */
      if (!this.pageSize || (usedPerPage && this.pageSize !== usedPerPage)) {
        this.pageSize = usedPerPage || PaginatedListState.DEFAULT_PAGE_SIZE;
      }

      if (!this.totalItems || (usedTotal && this.totalItems !== usedTotal)) {
        this.totalItems = usedTotal || null;
      }

      return results;
    });
  }

  protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural {
    /*
     * Support use of page[near]=
     */
    if (params.page?.near && this.hasItems()) {
      delete params.page?.near;

      const nextPage = this.location.page < page;

      const offsets = this.getPages().map((page) => {
        if ('payload' in page.items) {
          return page.items.payload.meta?.page?.offset || 0;
        }

        return 0;
      });

      const minOffset = Math.min(...offsets);
      const maxOffset = Math.max(...offsets);

      const limit = this.pageSize || PaginatedListState.DEFAULT_PAGE_SIZE;

      params.page ||= {};
      params.page.offset = nextPage ? maxOffset + limit : Math.max(minOffset - limit, 0);
    }

    return params;
  }

  /**
   * Get the parameters that should be passed in the API request.
   * Do not include page offset unless subclass overrides loadPage.
   *
   * @abstract
   * @see loadPage
   */
  protected requestParams(): PaginatedListRequestParams {
    return this.params;
  }

  /**
   * Update the `this.params` object, calling `refresh` if they have changed.
   * Use `requestParams` for converting `this.params` into API parameters
   *
   * @param newParams
   * @param page
   * @see requestParams
   */
  public refreshParams(newParams: P, page: number): Promise<void> {
    if (this.isEmpty() || this.paramsChanged(newParams)) {
      this.params = newParams;

      return this.refresh(page);
    }

    return Promise.resolve();
  }

  public refresh(page: number = 1): Promise<void> {
    this.initialLoading = true;
    this.loadingPrev = false;
    this.loadingNext = false;

    this.clear();

    return this.goto(page);
  }

  public goto(page: number): Promise<void> {
    this.location = { page };

    if (!this.initialLoading) {
      this.loadingPage = true;
    }

    return this.loadPage(page)
      .then((results) => {
        this.pages = [];
        this.parseResults(this.location.page, results);
      })
      .finally(() => {
        this.initialLoading = false;
        this.loadingPage = false;
      });
  }

  public getPages(): Page<T>[] {
    return this.pages;
  }
  public getLocation(): PaginationLocation {
    return this.location;
  }

  public isLoading(): boolean {
    return this.initialLoading || this.loadingNext || this.loadingPrev || this.loadingPage;
  }
  public isInitialLoading(): boolean {
    return this.initialLoading;
  }
  public isLoadingPrev(): boolean {
    return this.loadingPrev;
  }
  public isLoadingNext(): boolean {
    return this.loadingNext;
  }

  /**
   * Returns true when the number of items across all loaded pages is not 0.
   *
   * @see isEmpty
   */
  public hasItems(): boolean {
    return !!this.getAllItems().length;
  }

  /**
   * Returns true when there aren't any items *and* the state has already done its initial loading.
   * If you want to know whether there are items regardless of load state, use `hasItems()` instead
   *
   * @see hasItems
   */
  public isEmpty(): boolean {
    return !this.isInitialLoading() && !this.hasItems();
  }

  public hasPrev(): boolean {
    return !!this.pages[0]?.hasPrev;
  }
  public hasNext(): boolean {
    return !!this.pages[this.pages.length - 1]?.hasNext;
  }

  /**
   * Stored state parameters.
   */
  public getParams(): P {
    return this.params;
  }

  protected getNextPageNumber(): number {
    const pg = this.pages[this.pages.length - 1]?.number;

    if (pg && !isNaN(pg)) {
      return pg + 1;
    } else {
      return this.location.page;
    }
  }
  protected getPrevPageNumber(): number {
    const pg = this.pages[0]?.number;

    if (pg && !isNaN(pg)) {
      // If the calculated page number is less than 1,
      // return 1 as the prev page (first possible page number)
      return Math.max(pg - 1, 1);
    } else {
      return this.location.page;
    }
  }

  protected paramsChanged(newParams: P): boolean {
    return Object.keys(newParams).some((key) => this.getParams()[key] !== newParams[key]);
  }

  protected getAllItems(): T[] {
    return this.getPages()
      .map((pg) => pg.items)
      .flat();
  }

  /**
   * In the last request, has the user searched for a model?
   */
  isSearchResults(): boolean {
    return !!this.params.q;
  }

  public push(model: T): void {
    const lastPage = this.pages[this.pages.length - 1];

    if (lastPage && lastPage.items.length < (this.pageSize || PaginatedListState.DEFAULT_PAGE_SIZE)) {
      lastPage.items.push(model);
    } else {
      this.pages.push({
        number: lastPage ? lastPage.number + 1 : 1,
        items: [model],
        hasNext: lastPage.hasNext,
        hasPrev: Boolean(lastPage),
      });
    }

    m.redraw();
  }

  getSort(): string {
    return this.params.sort || '';
  }

  sortMap(): SortMap {
    return {};
  }

  sortValue(sort: SortMapItem): string | undefined {
    return typeof sort === 'string' ? sort : sort?.sort;
  }

  currentSort(): string | undefined {
    return this.sortValue(this.sortMap()[this.getSort()]);
  }

  changeSort(sort: string) {
    this.refreshParams(
      {
        ...this.params,
        sort: sort,
      },
      1
    );
  }

  changeFilter(key: string, value: any) {
    this.refreshParams(
      {
        ...this.params,
        filter: {
          ...this.params.filter,
          [key]: value,
        },
      },
      1
    );
  }

  remove(model: T): void {
    const page = this.pages.find((pg) => pg.items.includes(model));

    if (page) {
      page.items = page.items.filter((item) => item !== model);
    }
  }
}
