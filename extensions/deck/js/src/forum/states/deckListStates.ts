import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import PostListState from 'flarum/forum/states/PostListState';
import type PaginatedListState from 'flarum/common/states/PaginatedListState';
import type { PaginatedListRequestParams } from 'flarum/common/states/PaginatedListState';
import type { ApiQueryParamsPlural } from 'flarum/common/Store';

/**
 * Deck never shows a total, and counting every match can cost far more than
 * the page itself (a group's posts can't be counted from an index), so its
 * lists ask the API to skip the count with `page[total]=0`.
 */
const withoutTotal = (params: PaginatedListRequestParams): PaginatedListRequestParams => ({
  ...params,
  page: { ...(params.page ?? {}), total: 0 },
});

/**
 * A list's request as the store takes it, as core's own lists do: for asking
 * the API something alongside a list, and getting what its items get.
 */
export function asQuery(params: PaginatedListRequestParams): ApiQueryParamsPlural {
  const { include, ...query } = params;

  // Left out when there's none: a key without a value is sent as a bare
  // `?include`, which asks for nothing and turns every default off.
  if (include === undefined) return query;

  return { ...query, include: Array.isArray(include) ? include.join(',') : include };
}

/**
 * The next page starts where what the column holds ends, not at a multiple of
 * the page size. Some columns shrink: a discussion read leaves Unread, a post
 * whose flags are cleared leaves Flagged. The same thing leaves the server's
 * list, so everything after it moves up, and a page asked for by number
 * would skip as many as have gone. Items the column moved to the top itself
 * (realtime) are ahead on the server too, so they count.
 */
function nextPageFromHeld(state: PaginatedListState<any>, params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural {
  const location = (state as any).location as { page: number };

  if (page > location.page && !params.page?.near) {
    const held = state.getPages().reduce((count, held) => count + held.items.length, 0);

    params.page = { ...params.page, offset: held };
  }

  return params;
}

/**
 * Once items leaving have emptied a column while the server has more, the
 * next page takes their place; a column only shows "nothing here" when there
 * really is nothing. Reading sends no realtime event, so this is what prune()
 * is for.
 */
export function refillIfEmpty(state: PaginatedListState<any>): Promise<void> {
  if (state.hasItems() || !state.hasNext()) return Promise.resolve();

  return state.loadNext();
}

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

  protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural {
    return nextPageFromHeld(this, super.mutateRequestParams(params, page), page);
  }

  revalidate(): Promise<void> {
    const before = this.pages;

    return super.revalidate().then(() => {
      if (this.pages !== before) this.extraDiscussions = [];
    });
  }
}

export class DeckPostListState extends PostListState {
  /**
   * Posts as the discussion page asks for them: no include, so the endpoint's
   * defaults, which extensions add to (likes, flags, mentions, ...). Core's
   * PostListState names a few, and extensions add theirs to that list, but
   * naming any at all turns every default off.
   *
   * With no sort, the server's own order: a filter's, such as flagged posts by
   * when they were flagged.
   */
  requestParams(): PaginatedListRequestParams {
    const params = withoutTotal(super.requestParams());

    delete params.include;
    if (!this.params.sort) delete params.sort;

    return params;
  }

  protected mutateRequestParams(params: ApiQueryParamsPlural, page: number): ApiQueryParamsPlural {
    return nextPageFromHeld(this, super.mutateRequestParams(params, page), page);
  }

  revalidate(): Promise<void> {
    const before = this.pages;

    return super.revalidate().then(() => {
      if (this.pages !== before) this.extraPosts = [];
    });
  }
}
