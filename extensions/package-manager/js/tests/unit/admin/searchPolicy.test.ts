import {
  MIN_QUERY_LENGTH,
  SEARCH_DEBOUNCE_MS,
  liveSearchPlan,
  submittedSearchPlan,
} from '../../../src/admin/searchPolicy';

describe('searching as you type', () => {
  it('searches once the query is long enough to mean something', () => {
    expect(liveSearchPlan('ta', null)).toEqual({ search: true, delayMs: SEARCH_DEBOUNCE_MS });
    expect(liveSearchPlan('tags', null)).toEqual({ search: true, delayMs: SEARCH_DEBOUNCE_MS });
  });

  /* One letter matches most of Packagist, and every query passes through it. */
  it('does not search on a single character', () => {
    expect(liveSearchPlan('t', null).search).toBe(false);
    expect(MIN_QUERY_LENGTH).toBe(2);
  });

  /* An emptied box means "show me what there is", which is what the tab opens with. */
  it('restores the default list when the box is cleared', () => {
    expect(liveSearchPlan('', 'tags')).toEqual({ search: true, delayMs: SEARCH_DEBOUNCE_MS });
  });

  /*
   * 🚨 Typing a character and deleting it, or blurring and refocusing, must not
   * re-run a search whose results are already on screen. Under a stale-response
   * guard the visible effect is a list that flickers for nothing.
   */
  it('does not repeat the search already on screen', () => {
    expect(liveSearchPlan('tags', 'tags').search).toBe(false);
    expect(liveSearchPlan('  tags  ', 'tags').search).toBe(false);
  });

  it('treats surrounding whitespace as no change', () => {
    expect(liveSearchPlan('tags ', 'tags').search).toBe(false);
    expect(liveSearchPlan(' ', '').search).toBe(false);
  });

  it('searches again when the query actually changes', () => {
    expect(liveSearchPlan('tagsx', 'tags').search).toBe(true);
  });

  /* The very first render has nothing on screen, so an empty query must search. */
  it('searches on open, before anything has been searched', () => {
    expect(liveSearchPlan('', null).search).toBe(true);
  });
});

describe('pressing the button or Return', () => {
  /*
   * 🚨 An explicit action is never refused by a heuristic meant for keystrokes.
   * A control that silently does nothing is worse than no control.
   */
  it('searches immediately, even for a query too short to type-search', () => {
    expect(submittedSearchPlan()).toEqual({ search: true, delayMs: 0 });
  });
});
