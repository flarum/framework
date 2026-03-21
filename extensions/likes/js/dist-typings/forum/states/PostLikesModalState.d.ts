import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import User from 'flarum/common/models/User';
export interface PostLikesModalListParams extends PaginatedListParams {
    filter: {
        liked: string;
    };
    page?: {
        offset?: number;
        limit: number;
    };
}
export default class PostLikesModalState<P extends PostLikesModalListParams = PostLikesModalListParams> extends PaginatedListState<User, P> {
    constructor(params: P, page?: number);
    get type(): string;
}
