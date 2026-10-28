import app from '../../app';
import type Model from '../../Model';
import RelationInput from './RelationInput';

/**
 * The paged, searchable records of one related type, outside a component: what `RelationInput`
 * loads, for menus that render the records as their own items (the resource table's relationship
 * filter). Searches send `filter[q]`; "Load more" appends the next page; only the latest request
 * lands.
 *
 * @experimental
 */
export default class RelationOptions {
  items: Model[] = [];
  hasMore: boolean = false;
  isLoading: boolean = false;
  loaded: boolean = false;
  /** The last request failed; `retry()` loads again. */
  failed: boolean = false;
  query: string = '';

  protected searchTimeout: ReturnType<typeof setTimeout> | null = null;
  protected requestCount: number = 0;

  constructor(
    readonly type: string,
    readonly pageSize: number = RelationInput.PAGE_SIZE
  ) {}

  searchable(): boolean {
    return RelationInput.isSearchable(this.type);
  }

  /** The first page, once. */
  ensureLoaded(): void {
    if (!this.loaded && !this.isLoading) this.loadItems();
  }

  search(query: string): void {
    this.query = query;
    this.hasMore = false;

    // Drop any response still in flight for the previous query.
    this.requestCount++;
    this.isLoading = false;

    this.cancel();
    this.searchTimeout = setTimeout(() => this.loadItems(), RelationInput.SEARCH_DELAY);
  }

  cancel(): void {
    if (this.searchTimeout) clearTimeout(this.searchTimeout);
    this.searchTimeout = null;
  }

  fetchPage(offset: number): Promise<{ items: Model[]; hasMore: boolean }> {
    const params: Record<string, unknown> = { page: { offset, limit: this.pageSize } };

    if (this.query) params.filter = { q: this.query };

    return app.store.find<Model[]>(this.type, params).then((results) => ({
      items: [...results],
      hasMore: !!results.payload?.links?.next,
    }));
  }

  /** Loads again after a failure: the next page when items are shown, else the first. */
  retry(): Promise<void> {
    return this.load(this.items.length > 0 && this.loaded);
  }

  loadItems(): Promise<void> {
    return this.load(false);
  }

  loadMore(): Promise<void> {
    return this.load(true);
  }

  protected async load(append: boolean): Promise<void> {
    const request = ++this.requestCount;

    this.isLoading = true;
    this.failed = false;

    try {
      const page = await this.fetchPage(append ? this.items.length : 0);

      if (request !== this.requestCount) return;

      const known = new Set(append ? this.items.map((item) => item.id()) : []);
      this.items = append ? [...this.items, ...page.items.filter((item) => !known.has(item.id()))] : page.items;
      this.hasMore = page.hasMore;
      this.loaded = true;
    } catch (error) {
      if (request !== this.requestCount) return;

      console.error(`Error loading ${this.type}:`, error);
      this.failed = true;
    } finally {
      if (request === this.requestCount) {
        this.isLoading = false;
        m.redraw();
      }
    }
  }
}
