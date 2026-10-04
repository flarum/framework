import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import PostListState from 'flarum/forum/states/PostListState';
import type { PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';
export declare class DeckDiscussionListState extends DiscussionListState {
    requestParams(): PaginatedListRequestParams;
}
export declare class DeckPostListState extends PostListState {
    requestParams(): PaginatedListRequestParams;
}
