import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import PostListState from 'flarum/forum/states/PostListState';
import type { PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';

/**
 * Deck never shows a total, and counting every match can cost far more than
 * the page itself (a group's posts can't be counted from an index), so its
 * lists ask the API to skip the count with `page[total]=0`.
 */
const withoutTotal = (params: PaginatedListRequestParams): PaginatedListRequestParams => ({
  ...params,
  page: { ...(params.page ?? {}), total: 0 },
});

export class DeckDiscussionListState extends DiscussionListState {
  requestParams(): PaginatedListRequestParams {
    return withoutTotal(super.requestParams());
  }
}

export class DeckPostListState extends PostListState {
  requestParams(): PaginatedListRequestParams {
    return withoutTotal(super.requestParams());
  }
}
