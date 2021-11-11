import PaginatedListState, { Page, PaginatedListParams } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';
export interface IRequestParams {
    include: string[];
    filter: Record<string, string>;
    sort?: string;
}
export interface DiscussionListParams extends PaginatedListParams {
    sort?: string;
}
export default class DiscussionListState<P extends DiscussionListParams = DiscussionListParams> extends PaginatedListState<Discussion, P> {
    protected extraDiscussions: Discussion[];
    constructor(params: P, page?: number);
    get type(): string;
    requestParams(): IRequestParams;
    protected loadPage(page?: number): Promise<Discussion[]>;
    clear(): void;
    /**
     * Get a map of sort keys (which appear in the URL, and are used for
     * translation) to the API sort value that they represent.
     */
    sortMap(): any;
    /**
     * In the last request, has the user searched for a discussion?
     */
    isSearchResults(): boolean;
    removeDiscussion(discussion: Discussion): void;
    /**
     * Add a discussion to the top of the list.
     */
    addDiscussion(discussion: Discussion): void;
    protected getAllItems(): Discussion[];
    getPages(): Page<Discussion>[];
}
