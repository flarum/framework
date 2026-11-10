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

  /**
   * The filters the list is currently showing results for, as the API takes
   * them: `q` run through the gambits and folded into `filter` — the same
   * parsing `Store.find()` does before a `GET` — and whatever extensions add
   * — the tag of a tag page, say — already in place, since this is the same
   * thing `requestParams()` asks the API for.
   *
   * Gambits like `tag:x` or `is:unread` only take effect for a `GET` today,
   * applied client-side on the way out in `Store.find()`; the server never
   * parses `q`. Anything sent `q` as-is — `markAllAsRead()`'s `POST`, and the
   * confirmation text this feeds `IndexPage` — would otherwise run a fulltext
   * search for the literal gambit text instead of the filter it stands for.
   *
   * Filters that single nothing out are left off, so a list showing every
   * discussion is recognisable as one however it was arrived at.
   */
  public activeFilters(): Record<string, any> {
    let filter = { ...((this.requestParams().filter ?? {}) as Record<string, any>) };

    // The whole filter goes through, as in `Store.find()`, not `q` alone: a
    // gambit with `predicates` (flarum/tags' `tag:`) appends to a filter key
    // already set, such as the tag of a tag page, rather than replacing it.
    if (filter.q) {
      filter = app.search.gambits.apply(this.type, filter);
    }

    // An empty value narrows nothing and is dropped — an empty `q` searches
    // for nothing, an empty `author` names no one — matching the server's
    // `narrowingFilters()`. The exception is filters switched on by their
    // presence alone (a bare `hidden`), whose empty value still narrows.
    const presenceFilters = ['hidden', '-hidden'];

    return Object.fromEntries(
      Object.entries(filter).filter(([key, value]) => {
        if (value === undefined || value === null) return false;
        if (presenceFilters.includes(key)) return true;

        return value !== '';
      })
    );
  }

  /**
   * Whether marking this list read covers every discussion the user can see.
   *
   * True for an unfiltered list, and also for one filtered to nothing but
   * `unread` — that is every unread discussion, which is the same set the
   * unfiltered list marks read. The server records both as the single
   * `markedAllAsReadAt` timestamp rather than a row per discussion, so the
   * client treats them the same: it refetches the user for the server's
   * timestamp, and `IndexPage` offers the "all discussions" confirmation.
   */
  public marksEverythingAsRead(): boolean {
    const keys = Object.keys(this.activeFilters());

    return keys.length === 0 || (keys.length === 1 && keys[0] === 'unread');
  }

  /**
   * Mark the discussions in this list as read.
   *
   * The API is given the filters the list was fetched with, so a filtered list
   * only marks its own discussions read rather than the whole forum. With no
   * filters it is every discussion, which the server still records as one
   * timestamp on the user instead of a row per discussion.
   */
  public markAllAsRead(): Promise<void> {
    const filter = this.activeFilters();
    const everything = this.marksEverythingAsRead();
    const user = app.session.user;

    // Captured before the request is sent — both the discussions and the post
    // numbers to mark them read up to — not re-read from `this` once it
    // resolves. This state object is shared across navigation (switching tags,
    // say), so by the time the response comes back `getAllItems()` may already
    // belong to a different list; and reading `lastPostNumber()` only on
    // resolution would let a realtime reply arriving mid-request be shown as
    // read locally even though the server was never asked to mark it.
    const reads = this.getAllItems()
      .map((discussion) => ({ discussion, lastPostNumber: discussion.lastPostNumber() }))
      // Nothing to have read up to in a discussion whose posts are all gone.
      // Writing null would read as never having read it. The type guard also
      // narrows `lastPostNumber` to a number for the write below.
      .filter((read): read is { discussion: Discussion; lastPostNumber: number } => read.lastPostNumber != null);

    return app
      .request({
        method: 'POST',
        url: app.forum.attribute('apiUrl') + '/discussions/read',
        params: { filter },
      })
      .then(() => {
        reads.forEach(({ discussion, lastPostNumber }) => {
          discussion.pushAttributes({ lastReadPostNumber: lastPostNumber });
        });

        // The server stamped `marked_all_as_read_at` from its own clock; the
        // old `user.save()` read that value back from the response. In the
        // "everything" case refetch the user so unread badges follow the
        // server's time rather than the browser's, which may be wrong.
        const settled = everything && user ? app.store.find('users', user.id()!) : Promise.resolve();

        return settled.then(() => {
          m.redraw();
        });
      });
  }
}
