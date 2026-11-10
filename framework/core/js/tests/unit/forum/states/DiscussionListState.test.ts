import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';

beforeAll(() => bootstrapForum());

describe('DiscussionListState', () => {
  describe('requestParams', () => {
    // Regression test for #4583.
    //
    // Extenders (tags, subscriptions, …) mutate `params.filter` inside a
    // `requestParams` extender callback. If `requestParams` returns
    // `this.params.filter` by reference, those mutations leak back into the
    // stored state — and on the next mount `paramsChanged()` falsely reports
    // a change, wiping the paginated cache and resetting the list to page 1.
    test('does not leak this.params.filter to callers', () => {
      const state = new DiscussionListState({ filter: { tag: 'foo' } } as any);

      const out = state.requestParams() as { filter: Record<string, string> };
      out.filter.injectedByExtender = 'yes';

      expect((state as any).params.filter).toEqual({ tag: 'foo' });
      expect((state as any).params.filter.injectedByExtender).toBeUndefined();
    });
  });

  // The API reports the page size it used as `meta.page.limit`. The list needs
  // it so "Load more" asks for the offset where the preloaded page ended.
  describe('page size', () => {
    afterEach(() => jest.restoreAllMocks());

    const preload = (meta: Record<string, any>) =>
      jest.spyOn(app, 'preloadedApiDocument').mockReturnValue(Object.assign([], { payload: { data: [], meta } }) as any);

    test('is taken from the limit the preloaded response was served with', async () => {
      preload({ page: { total: 40, offset: 0, limit: 21 } });

      const state = new DiscussionListState({});
      await (state as any).loadPage(1);

      expect(state.pageSize).toBe(21);
    });

    test('still honors the legacy `perPage` meta', async () => {
      preload({ perPage: 15 });

      const state = new DiscussionListState({});
      await (state as any).loadPage(1);

      expect(state.pageSize).toBe(15);
    });

    test('falls back to the default without either', async () => {
      preload({});

      const state = new DiscussionListState({});
      await (state as any).loadPage(1);

      expect(state.pageSize).toBe(DiscussionListState.DEFAULT_PAGE_SIZE);
    });

    test('is learned from the limit a later request was served with', async () => {
      jest.spyOn(app, 'preloadedApiDocument').mockReturnValue(null as any);
      jest
        .spyOn(app.store, 'find')
        .mockResolvedValue(Object.assign([], { payload: { data: [], meta: { page: { total: 40, offset: 0, limit: 21 } } } }) as any);

      const state = new DiscussionListState({});
      await (state as any).loadPage(1);

      expect(state.pageSize).toBe(21);
    });
  });
});
