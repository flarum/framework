import PaginatedListState, { Page, PaginatedListParams, PaginatedListRequestParams } from '../../common/states/PaginatedListState';
import Post from '../../common/models/Post';
import { ApiResponsePlural } from '../../common/Store';
import EventEmitter from '../../common/utils/EventEmitter';
export interface PostListParams extends PaginatedListParams {
    sort?: string;
}
export default class PostListState<P extends PostListParams = PostListParams> extends PaginatedListState<Post, P> {
    protected extraPosts: Post[];
    protected eventEmitter: EventEmitter;
    constructor(params: P, page?: number, pageSize?: number | null);
    get type(): string;
    requestParams(): PaginatedListRequestParams;
    protected loadPage(page?: number): Promise<ApiResponsePlural<Post>>;
    clear(): void;
    /**
     * Get a map of sort keys (which appear in the URL, and are used for
     * translation) to the API sort value that they represent.
     */
    sortMap(): any;
    protected getAllItems(): Post[];
    getPages(): Page<Post>[];
}
