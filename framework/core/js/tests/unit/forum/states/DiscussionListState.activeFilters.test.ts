import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

/**
 * `activeFilters()` feeds both `markAllAsRead()`'s request and the
 * confirmation text `IndexPage` shows before sending it. A search term like
 * `author:x` or `is:unread` is only ever turned into a `filter[...]` entry on
 * the frontend — inside `Store.find()`, via `app.search.gambits.apply()` —
 * and the server never parses `q` itself. Without the same parsing here, a
 * filtered mark-as-read would send the gambit text as a literal fulltext
 * query instead of the filter it stands for.
 */
describe('DiscussionListState.activeFilters', () => {
  test('parses a key:value gambit out of q into its filter, dropping q', () => {
    const state = new DiscussionListState({ q: 'author:sarah' } as any);

    expect(state.activeFilters()).toEqual({ author: 'sarah' });
  });

  test('parses a boolean gambit out of q into its filter', () => {
    const state = new DiscussionListState({ q: 'is:unread' } as any);

    expect(state.activeFilters()).toEqual({ unread: true });
  });

  test('leaves unmatched text in q alongside a parsed gambit', () => {
    const state = new DiscussionListState({ q: 'author:sarah hello' } as any);

    expect(state.activeFilters()).toEqual({ author: 'sarah', q: 'hello' });
  });

  test('a plain fulltext search with no gambit stays as q', () => {
    const state = new DiscussionListState({ q: 'hello world' } as any);

    expect(state.activeFilters()).toEqual({ q: 'hello world' });
  });

  test('a filter set directly (e.g. a tag page) is unaffected', () => {
    const state = new DiscussionListState({ filter: { tag: 'foo' } } as any);

    expect(state.activeFilters()).toEqual({ tag: 'foo' });
  });

  test('an unfiltered list reports no active filters', () => {
    const state = new DiscussionListState({} as any);

    expect(state.activeFilters()).toEqual({});
  });
});
