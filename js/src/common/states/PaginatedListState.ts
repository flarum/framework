import Model from '../Model';

export interface Page<TModel> {
  number: number;
  items: TModel[];
}

export interface PaginationLocation {
  page: number;
  startIndex?: number;
  endIndex?: number;
}

export default abstract class PaginatedListState<T extends Model> {
  protected location!: PaginationLocation;
  protected pageSize: number;

  protected pages: Page<T>[] = [];
  protected params: any = {};

  protected initialLoading: boolean = false;
  protected loadingPrev: boolean = false;
  protected loadingNext: boolean = false;

  hasPrev: boolean = false;
  hasNext: boolean = false;

  protected constructor(pageSize: number = 20) {
    this.pageSize = pageSize;
  }

  abstract get type(): string;

  protected parseResults(pageNum, results: T[]) {
    const page = {
      number: pageNum,
      items: results,
    };

    if (this.isEmpty() || pageNum > this.getNextPageNumber() - 1) {
      this.pages.push(page);
    } else {
      this.pages.unshift(page);
    }

    this.hasNext = !!results.payload?.links?.next;
    this.hasPrev = !!results.payload?.links?.prev;

    m.redraw();
  }

  /**
   * Load a new page of results.
   */
  protected loadPage(page = 1): Promise<T[]> {
    const params = this.requestParams();
    params.page = { offset: this.pageSize * (page - 1) };

    if (Array.isArray(params.include)) {
      params.include = params.include.join(',');
    }

    return app.store.find(this.type, params);
  }

  public hasItems(): boolean {
    return !!this.getAllItems().length;
  }

  public isEmpty(): boolean {
    return !this.isInitialLoading() && !this.hasItems();
  }

  public getLocation(): PaginationLocation {
    return this.location;
  }

  protected getAllItems(): T[] {
    return this.pages.map((pg) => pg.items).flat();
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
   * Get the parameters that should be passed in the API request.
   * Do not include page offset unless subclass overrides loadPage.
   *
   * @abstract
   * @see loadPage
   */
  protected requestParams(): any {
    return {};
  }

  /**
   * Stored state parameters.
   */
  public getParams(): any {
    return this.params;
  }

  /**
   * Update the `this.params` object, calling `refresh` if they have changed.
   * Use `requestParams` for converting `this.params` into API parameters
   *
   * @param newParams
   * @see requestParams
   */
  public refreshParams(newParams) {
    if (this.isEmpty() || Object.keys(newParams).some((key) => this.requestParams()[key] !== newParams[key])) {
      this.params = newParams;

      return this.refresh();
    }
  }

  public refresh(page: number = 1) {
    this.initialLoading = true;
    this.loadingPrev = false;
    this.loadingNext = false;

    m.redraw();

    this.location = { page };

    return this.loadPage()
      .then((results: T[]) => {
        this.pages = [];
        this.parseResults(this.location.page, results);
      })
      .finally(() => (this.initialLoading = false));
  }

  public clear() {
    this.pages = [];
    m.redraw();
  }

  public loadMore() {
    if (this.loadingNext) return Promise.resolve();

    this.loadingNext = true;

    const page: number = this.getNextPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingNext = false));
  }

  public loadPrev() {
    if (this.loadingPrev || this.getLocation().page === 1) return Promise.resolve();

    this.loadingPrev = true;

    const page: number = this.getPrevPageNumber();

    return this.loadPage(page)
      .then(this.parseResults.bind(this, page))
      .finally(() => (this.loadingPrev = false));
  }

  public getPages() {
    return this.pages;
  }

  protected getNextPageNumber(): number {
    const pg = this.pages[this.pages.length - 1]?.number;

    if (pg && !isNaN(pg)) {
      return pg + 1;
    } else {
      return this.location.page;
    }
  }

  protected getPrevPageNumber() {
    const pg = this.pages[0]?.number;

    if (pg && !isNaN(pg)) {
      // If the calculated page number is less than 1,
      // return 1 as the prev page (first possible page number)
      return Math.max(pg - 1, 1);
    } else {
      return this.location.page;
    }
  }
}
