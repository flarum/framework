import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import PostListState from 'flarum/forum/states/PostListState';
import type PaginatedListState from 'flarum/common/states/PaginatedListState';
import type { PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';
import type { ApiQueryParamsPlural } from 'flarum/common/Store';
/**
 * A list's request as the store takes it, as core's own lists do: for asking
 * the API something alongside a list, and getting what its items get.
 */
export declare function asQuery(params: PaginatedListRequestParams): ApiQueryParamsPlural;
/**
 * Once items leaving have emptied a column while the server has more, the
 * next page takes their place; a column only shows "nothing here" when there
 * really is nothing. Reading sends no realtime event, so this is what prune()
 * is for.
 */
export declare function refillIfEmpty(state: PaginatedListState<any>): Promise<void>;
export declare class DeckDiscussionListState extends DiscussionListState {
    requestParams(): PaginatedListRequestParams;
    protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural;
    revalidate(): Promise<void>;
}
export declare class DeckPostListState extends PostListState {
    /**
     * Posts as the discussion page asks for them: no include, so the endpoint's
     * defaults, which extensions add to (likes, flags, mentions, ...). Core's
     * PostListState names a few, and extensions add theirs to that list, but
     * naming any at all turns every default off.
     *
     * With no sort, the server's own order: a filter's, such as flagged posts by
     * when they were flagged.
     */
    requestParams(): PaginatedListRequestParams;
    protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural;
    revalidate(): Promise<void>;
}
