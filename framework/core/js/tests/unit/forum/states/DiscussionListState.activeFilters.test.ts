import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import { KeyValueGambit } from '../../../../src/common/query/IGambit';

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

  test('a predicate gambit in q adds to a filter the list already sets, as Store.find() does', () => {
    // Stands in for flarum/tags' TagGambit, which core doesn't ship.
    class PredicateTagGambit extends KeyValueGambit {
      predicates = true;
      key() {
        return 'tag';
      }
      hint() {
        return 'tag';
      }
      filterKey() {
        return 'tag';
      }
      gambitValueToFilterValue(value: string) {
        return [value];
      }
    }

    const original = app.search.gambits.gambits.discussions;
    app.search.gambits.gambits.discussions = [...original, PredicateTagGambit];

    try {
      // A tag page (filter.tag set directly) with `tag:other` typed in search.
      const state = new DiscussionListState({ filter: { tag: ['foo'] }, q: 'tag:other' } as any);
      const expected = app.search.gambits.apply('discussions', { tag: ['foo'], q: 'tag:other' });

      expect(state.activeFilters()).toEqual({ tag: ['foo', 'other'] });
      expect(state.activeFilters()).toEqual(Object.fromEntries(Object.entries(expected).filter(([, v]) => v !== '')));
    } finally {
      app.search.gambits.gambits.discussions = original;
    }
  });

  test('an unfiltered list reports no active filters', () => {
    const state = new DiscussionListState({} as any);

    expect(state.activeFilters()).toEqual({});
  });

  test('a presence-only filter is kept even when its value is empty', () => {
    // `hidden` is switched on by its presence alone, so a bare value still
    // narrows the list and must survive, matching the server.
    const state = new DiscussionListState({ filter: { hidden: '' } } as any);

    expect(state.activeFilters()).toEqual({ hidden: '' });
  });

  test.each(['author', 'sticky', 'locked', 'custom'])('preserves an empty %s filter', (key) => {
    const state = new DiscussionListState({ filter: { [key]: '' } } as any);

    expect(state.activeFilters()).toEqual({ [key]: '' });
    expect(state.marksEverythingAsRead()).toBe(false);
  });

  test.each([null, undefined])('preserves a bare presence filter serialized from %s', (value) => {
    const state = new DiscussionListState({ filter: { hidden: value } } as any);

    expect(state.activeFilters()).toEqual({ hidden: '' });
    expect(state.marksEverythingAsRead()).toBe(false);
  });

  test('drops an empty fulltext query', () => {
    const state = new DiscussionListState({ filter: { q: '' } } as any);

    expect(state.activeFilters()).toEqual({});
    expect(state.marksEverythingAsRead()).toBe(true);
  });
});
