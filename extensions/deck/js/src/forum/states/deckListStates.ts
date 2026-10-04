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

/*
 * A column's refresh is the server's answer: whatever Deck put at the top
 * itself goes too, not only what the new first page repeats. Something that
 * still belongs is on that page anyway, as it has the newest activity; one
 * that no longer does (a discussion just read, in Unread) would otherwise
 * never leave. Core keeps them on the index, where realtime is the only other
 * source. A failed refresh leaves `pages` as it was, and the extras with it.
 */

export class DeckDiscussionListState extends DiscussionListState {
  requestParams(): PaginatedListRequestParams {
    return withoutTotal(super.requestParams());
  }

  revalidate(): Promise<void> {
    const before = this.pages;

    return super.revalidate().then(() => {
      if (this.pages !== before) this.extraDiscussions = [];
    });
  }
}

export class DeckPostListState extends PostListState {
  requestParams(): PaginatedListRequestParams {
    return withoutTotal(super.requestParams());
  }

  revalidate(): Promise<void> {
    const before = this.pages;

    return super.revalidate().then(() => {
      if (this.pages !== before) this.extraPosts = [];
    });
  }
}
