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
}
