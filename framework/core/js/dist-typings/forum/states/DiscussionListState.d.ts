import PaginatedListState, { Page, PaginatedListParams, PaginatedListRequestParams, type SortMap } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';
import { ApiResponsePlural } from '../../common/Store';
import EventEmitter from '../../common/utils/EventEmitter';
export interface DiscussionListParams extends PaginatedListParams {
    sort?: string;
}
export default class DiscussionListState<P extends DiscussionListParams = DiscussionListParams> extends PaginatedListState<Discussion, P> {
    protected extraDiscussions: Discussion[];
    protected eventEmitter: EventEmitter;
    constructor(params: P, page?: number);
    get type(): string;
    requestParams(): PaginatedListRequestParams;
    protected loadPage(page?: number): Promise<ApiResponsePlural<Discussion>>;
    clear(): void;
    /**
     * Get a map of sort keys (which appear in the URL, and are used for
     * translation) to the API sort value that they represent.
     */
    sortMap(): SortMap;
    removeDiscussion(discussion: Discussion): void;
    deleteDiscussion(discussion: Discussion): void;
    /**
     * Add a discussion to the top of the list.
     */
    addDiscussion(discussion: Discussion): void;
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
    revalidate(): Promise<void>;
    protected getAllItems(): Discussion[];
    getPages(): Page<Discussion>[];
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
    activeFilters(): Record<string, any>;
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
    marksEverythingAsRead(): boolean;
    /**
     * Mark the discussions in this list as read.
     *
     * The API is given the filters the list was fetched with, so a filtered list
     * only marks its own discussions read rather than the whole forum. With no
     * filters it is every discussion, which the server still records as one
     * timestamp on the user instead of a row per discussion.
     */
    markAllAsRead(): Promise<void>;
}
