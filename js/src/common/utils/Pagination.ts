export default class Pagination<T> {
  private readonly loadFunction: (page: number) => Promise<any>;

  public loading = {
    prev: false,
    next: false,
  };

  public page: number;

  public data: { [page: number]: T } = {};

  public pages: {
    first: number;
    last: number;
  };

  constructor(load: (page: number) => Promise<any>, page: number = 1) {
    this.loadFunction = load;
    this.page = page;

    this.pages = {
      first: page,
      last: page,
    };
  }

  clear() {
    this.data = {};
  }

  refresh(page: number) {
    this.clear();

    this.page = page;
    this.pages.last = page - 1;
    this.pages.first = page;

    return this.loadNext();
  }

  loadNext() {
    this.loading.next = true;
    const page = this.pages.last + 1;

    return this.load(
      page,
      () => (this.loading.next = false),
      () => (this.pages.last = this.page = page)
    );
  }

  loadPrev() {
    this.loading.prev = true;
    const page = this.pages.first - 1;

    return this.load(
      page,
      () => (this.loading.prev = false),
      () => (this.pages.first = this.page = page)
    );
  }

  private load(page, done, success) {
    return this.loadFunction(page)
      .then((out) => {
        done();
        success();

        this.data[this.page] = out;

        return out;
      })
      .catch((err) => {
        done();

        return Promise.reject(err);
      });
  }
}
