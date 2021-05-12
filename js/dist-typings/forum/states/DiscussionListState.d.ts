import PaginatedListState, { Page } from '../../common/states/PaginatedListState';
import Discussion from '../../common/models/Discussion';
export default class DiscussionListState extends PaginatedListState<Discussion> {
    protected extraDiscussions: Discussion[];
    constructor(params: any, page: number);
    get type(): string;
    requestParams(): any;
    protected loadPage(page?: number): any;
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
