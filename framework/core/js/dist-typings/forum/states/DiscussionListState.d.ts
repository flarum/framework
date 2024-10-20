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
    protected getAllItems(): Discussion[];
    getPages(): Page<Discussion>[];
}
