import app from '../../common/app';
import Model from '../Model';
import { ApiQueryParamsPlural, ApiResponsePlural } from '../Store';

export interface Page<TModel> {
  number: number;
  items: TModel[];

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
  protected location!: PaginationLocation;
  protected pageSize: number;

  protected pages: Page<T>[] = [];
  protected params: P = {} as P;

  protected initialLoading: boolean = false;
  protected loadingPrev: boolean = false;
  protected loadingNext: boolean = false;

  protected constructor(params: P = {} as P, page: number = 1, pageSize: number = 20) {
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
    if (this.loadingPrev || this.getLocation().page === 1) return Promise.resolve();

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
        offset: this.pageSize * (page - 1),
      },
      include,
    };

    return app.store.find<T[]>(this.type, params);
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

    this.location = { page };

    return this.loadPage()
      .then((results) => {
        this.pages = [];
        this.parseResults(this.location.page, results);
      })
      .finally(() => (this.initialLoading = false));
  }

  public getPages(): Page<T>[] {
    return this.pages;
  }
  public getLocation(): PaginationLocation {
    return this.location;
  }

  public isLoading(): boolean {
    return this.initialLoading || this.loadingNext || this.loadingPrev;
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
}
