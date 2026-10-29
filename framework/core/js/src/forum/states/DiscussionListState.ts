import app from '../../forum/app';
import PaginatedListState, { Page, PaginatedListParams, PaginatedListRequestParams, type SortMap } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';
import { ApiResponsePlural } from '../../common/Store';
import EventEmitter from '../../common/utils/EventEmitter';

export interface DiscussionListParams extends PaginatedListParams {
  sort?: string;
}

const globalEventEmitter = new EventEmitter();

export default class DiscussionListState<P extends DiscussionListParams = DiscussionListParams> extends PaginatedListState<Discussion, P> {
  protected extraDiscussions: Discussion[] = [];
  protected eventEmitter: EventEmitter;

  constructor(params: P, page: number = 1) {
    super(params, page, null);

    this.eventEmitter = globalEventEmitter.on('discussion.deleted', this.deleteDiscussion.bind(this));
  }

  get type(): string {
    return 'discussions';
  }

  requestParams(): PaginatedListRequestParams {
    // `filter` is cloned so extenders mutating it don't write back into
    // `this.params.filter` — that leak trips `paramsChanged()` on the next
    // mount and wipes the paginated cache (see #4583).
    const params = {
      include: ['user', 'lastPostedUser'],
      filter: { ...this.params.filter },
      sort: this.currentSort(),
    };

    if (this.params.q) {
      params.filter.q = this.params.q;
      params.include.push('mostRelevantPost', 'mostRelevantPost.user');
    }

    return params;
  }

  protected loadPage(page: number = 1): Promise<ApiResponsePlural<Discussion>> {
    const preloadedDiscussions = app.preloadedApiDocument<Discussion[]>();

    if (preloadedDiscussions) {
      this.initialLoading = false;
      this.pageSize = preloadedDiscussions.payload.meta?.perPage || DiscussionListState.DEFAULT_PAGE_SIZE;

      return Promise.resolve(preloadedDiscussions);
    }

    return super.loadPage(page);
  }

  clear(): void {
    super.clear();

    this.extraDiscussions = [];
  }

  /**
   * Get a map of sort keys (which appear in the URL, and are used for
   * translation) to the API sort value that they represent.
   */
  sortMap(): SortMap {
    const map: any = {};

    if (this.params.q) {
      map.relevance = '';
    }
    map.latest = '-lastPostedAt';
    map.top = '-commentCount';
    map.newest = '-createdAt';
    map.oldest = 'createdAt';
    map.az = 'title';
    map.za = '-title';

    return map;
  }

  removeDiscussion(discussion: Discussion): void {
    this.eventEmitter.emit('discussion.deleted', discussion);
  }

  deleteDiscussion(discussion: Discussion): void {
    // Match by id, not by object reference: realtime hands us a discussion
    // resolved through the store, which is a fresh instance whenever the store
    // no longer holds the one already on screen. A reference check would miss
    // that copy and leave the discussion showing twice.
    const id = discussion.id();

    if (id == null) return;

    for (const page of this.pages) {
      const index = page.items.findIndex((d) => d.id() === id);

      if (index !== -1) {
        page.items.splice(index, 1);
        break;
      }
    }

    const index = this.extraDiscussions.findIndex((d) => d.id() === id);

    if (index !== -1) {
      this.extraDiscussions.splice(index, 1);
    }

    m.redraw();
  }

  /**
   * Add a discussion to the top of the list.
   */
  addDiscussion(discussion: Discussion): void {
    this.removeDiscussion(discussion);
    this.extraDiscussions.unshift(discussion);

    m.redraw();
  }

  /**
   * Reconcile the realtime additions after a background revalidation.
   *
   * `refresh()` empties `extraDiscussions`, because it routes through
   * `clear()`. `revalidate()` deliberately does not clear anything before it
   * asks the API, so it rebuilds `pages` and leaves `extraDiscussions` alone —
   * and the first page it gets back contains exactly the discussions realtime
   * put there, since a new post is what moved them to the top. Left unhandled
   * they render from both places at once, which is what a reader coming back
   * to an idle tab sees as a duplicate.
   *
   * Only the ids the new pages actually contain are dropped. A revalidation
   * that failed resolves rather than rejecting and leaves `pages` untouched,
   * so clearing unconditionally would take realtime's additions off a list
   * that was never reloaded.
   */
  public revalidate(): Promise<void> {
    return super.revalidate().then(() => {
      // `super.getPages()`, not `this.getPages()`: the override below prepends
      // `extraDiscussions`, which would match every one of them against itself.
      const listed = new Set(super.getPages().flatMap((page) => page.items.map((discussion) => discussion.id())));

      this.extraDiscussions = this.extraDiscussions.filter((discussion) => !listed.has(discussion.id()));
    });
  }

  protected getAllItems(): Discussion[] {
    // `getPages()` already prepends `extraDiscussions`, and `super` flattens
    // that — concatenating them again here would count them twice.
    return super.getAllItems();
  }

  public getPages(): Page<Discussion>[] {
    const pages = super.getPages();

    if (this.extraDiscussions.length) {
      return [
        {
          number: -1,
          items: this.extraDiscussions,
        },
        ...pages,
      ];
    }

    return pages;
  }
}
