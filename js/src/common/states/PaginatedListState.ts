import * as Mithril from 'mithril';

import Model from '../Model';

export interface Page<TModel> {
  number: number;
  items: TModel[];

  // // maybe we can put page links here?
  // links: {[name: string]: string},
  // // or
  // previousPage?: string;
  // nextPage?: string;
}

export interface PaginationLocation {
  page: number;
  startIndex?: number;
  endIndex?: number;
}

export default abstract class PaginatedListState<T extends Model> {
  // TODO:
  //  - what does this page track? last page request? last page that is loaded
  //      (e.g. pg 7 if u have 3-7 loaded)
  //  - When do we set it?
  protected location!: PaginationLocation;

  // TODO: might want interface so pagination can work w/o model-specific stuff
  protected type: string;
  protected offset: number;

  protected pages: Page<T>[] = [];

  protected initialLoading: boolean = false;
  protected loadingPrev: boolean = false;
  protected loadingNext: boolean = false;

  protected hasPrev: boolean = false;
  protected hasNext: boolean = false;

  // TODO: this constructor is for class that implements
  // what data do we want passed?
  protected constructor(type: string, offset: number = 20) {
    this.type = type;
    this.offset = offset;
  }

  protected parseResults(pageNum, results: T[]) {
    const page = {
      number: pageNum,
      items: results,
    };

    // TODO: is this how we were thinking of treating this.pages ?
    if (this.isEmpty() || pageNum > this.pages[this.pages.length - 1].number) {
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
  protected loadPage(page = 1) {
    const params = this.defaultParams();
    params.page = { offset: this.offset * (page - 1) };
    params.include = params.include.join(',');

    return app.store.find(this.type, params);
  }

  public isEmpty(): boolean {
    return this.pages.length === 0;
  }
  public getLocation(): PaginationLocation {
    return this.location;
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

  defaultParams(): any {
    return {};
  }

  public refreshParams(newParams) {
    if (this.isEmpty() || Object.keys(newParams).some((key) => this.getParams()[key] !== newParams[key])) {
      this.params = newParams;

      this.refresh();
    }
  }

  public refresh() {
    this.initialLoading = true;
    this.loadingPrev = false;
    this.loadingNext = false;

    this.location = { page: 1 };

    return this.loadPage().then(
      (results: T[]) => {
        this.pages = [];
        this.parseResults(this.location.page, results);
      },
      () => {
        this.initialLoading = false;

        m.redraw();
      }
    );
  }

  public loadPrev() {
    // return
  }
}
