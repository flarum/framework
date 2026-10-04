import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import DeckState from '../../../../src/forum/states/DeckState';
import { POSTED, REMOVED, RENAMED } from '../../../../src/forum/columns/realtimeEvents';
import { PREFERENCE_KEY } from '../../../../src/forum/utils/deckLayout';
import { boot, column, discussionData, postData, postEventPayload, registerTestTypes, setLayout, sourceOf } from '../helpers';

beforeAll(() => {
  boot();
  registerTestTypes();
});

afterEach(() => {
  jest.useRealTimers();
});

const ids = (state: DeckState) => state.rows().map((row) => row.map((column) => column.config.id));

/** The layout as it was last saved, once the batched save has gone out. */
function savedIds(save: ReturnType<typeof setLayout>): Array<[string, number | undefined]> {
  jest.runOnlyPendingTimers();

  const preferences = save.mock.calls[save.mock.calls.length - 1][0] as Record<string, any>;

  return preferences[PREFERENCE_KEY].map((config: any) => [config.id, config.row]);
}

describe('rearranging', () => {
  it('moves a column into the other row, before the column it was dropped on', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b'), column('c', 1)]);
    const state = new DeckState();

    state.moveTo('a', 1, 0);

    expect(ids(state)).toEqual([['b'], ['a', 'c']]);
  });

  it('moves a column to the end of a row', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b'), column('c', 1)]);
    const state = new DeckState();

    state.moveTo('a', 1, 1);

    expect(ids(state)).toEqual([['b'], ['c', 'a']]);
  });

  it('opens the second row when a column is dropped into it empty', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b')]);
    const state = new DeckState();

    state.moveTo('b', 1, 0);

    expect(ids(state)).toEqual([['a'], ['b']]);
  });

  // A column whose extension was disabled isn't shown, so the drop index
  // doesn't count it, but it keeps its place for when it comes back.
  it('keeps hidden columns where they were', () => {
    jest.useFakeTimers();
    const save = setLayout([column('a'), column('h', 0, 'test-hidden'), column('b'), column('c')]);
    const state = new DeckState();

    state.moveTo('c', 0, 1);

    expect(ids(state)).toEqual([['a', 'c', 'b'], []]);
    expect(savedIds(save)).toEqual([
      ['a', 0],
      ['h', 0],
      ['c', 0],
      ['b', 0],
    ]);
  });

  it('saves a burst of moves once', () => {
    jest.useFakeTimers();
    const save = setLayout([column('a'), column('b'), column('c')]);
    const state = new DeckState();

    state.moveTo('a', 0, 2);
    state.moveTo('b', 0, 2);
    state.setWidth('c', 420);

    expect(save).not.toHaveBeenCalled();
    jest.runOnlyPendingTimers();
    expect(save).toHaveBeenCalledTimes(1);
  });

  describe('on a single strip (phones, short screens)', () => {
    it('joins the row of the column it lands after', () => {
      jest.useFakeTimers();
      setLayout([column('a'), column('b'), column('c', 1)]);
      const state = new DeckState();

      // Strip: a b | c  →  b c a
      state.moveFlat('a', 2);

      expect(ids(state)).toEqual([['b'], ['c', 'a']]);
    });

    it('joins the first column’s row when moved to the very start', () => {
      jest.useFakeTimers();
      setLayout([column('a'), column('b'), column('c', 1)]);
      const state = new DeckState();

      // Strip: a b | c  →  c a b
      state.moveFlat('c', 0);

      expect(ids(state)).toEqual([['c', 'a', 'b'], []]);
    });

    it('keeps both rows when reordering within one', () => {
      jest.useFakeTimers();
      setLayout([column('a'), column('b'), column('c', 1), column('d', 1), column('e', 1)]);
      const state = new DeckState();

      // Strip: a b | c d e  →  a b | c e d
      state.moveFlat('e', 3);

      expect(ids(state)).toEqual([
        ['a', 'b'],
        ['c', 'e', 'd'],
      ]);
    });

    it('joins the earlier row when dropped on the boundary between them', () => {
      jest.useFakeTimers();
      setLayout([column('a'), column('b'), column('c', 1), column('d', 1)]);
      const state = new DeckState();

      // Strip: a b | c d  →  a b d | c
      state.moveFlat('d', 2);

      expect(ids(state)).toEqual([['a', 'b', 'd'], ['c']]);
    });
  });
});

describe('moving without dragging', () => {
  it('moves a column one place along its row', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b'), column('c'), column('d', 1)]);
    const state = new DeckState();

    state.moveBy('a', 1, false);
    expect(ids(state)).toEqual([['b', 'a', 'c'], ['d']]);

    state.moveBy('c', -2, false);
    expect(ids(state)).toEqual([['c', 'b', 'a'], ['d']]);
  });

  it('stays within its row', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b'), column('c', 1)]);
    const state = new DeckState();

    expect(state.canMoveBy('b', 1, false)).toBe(false);
    expect(state.canMoveBy('c', -1, false)).toBe(false);

    state.moveBy('b', 1, false);
    expect(ids(state)).toEqual([['a', 'b'], ['c']]);
  });

  it('moves along the single strip, across the rows', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b'), column('c', 1)]);
    const state = new DeckState();

    expect(state.canMoveBy('b', 1, true)).toBe(true);

    // Strip: a b | c  →  a | c b
    state.moveBy('b', 1, true);
    expect(state.columns().map((column) => column.config.id)).toEqual(['a', 'c', 'b']);
    expect(ids(state)).toEqual([['a'], ['c', 'b']]);
  });

  it('counts only the columns that are shown', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('h', 0, 'test-hidden'), column('b')]);
    const state = new DeckState();

    expect(state.placeOf('b', false)).toEqual({ index: 1, count: 2, row: 0 });
    expect(state.canMoveBy('b', 1, false)).toBe(false);

    state.moveBy('b', -1, false);
    expect(ids(state)).toEqual([['b', 'a'], []]);
  });
});

describe('sizing', () => {
  it('keeps widths in bounds, and saves only once a drag ends', () => {
    jest.useFakeTimers();
    const save = setLayout([column('a'), column('b')]);
    const state = new DeckState();

    state.setWidth('a', 5000, false);
    jest.runOnlyPendingTimers();
    expect(state.columns()[0].config.width).toBe(900);
    expect(save).not.toHaveBeenCalled();

    state.setWidth('a', 500);
    jest.runOnlyPendingTimers();
    expect(save).toHaveBeenCalledTimes(1);
  });

  it('splits the rows evenly until told otherwise, within bounds', () => {
    jest.useFakeTimers();
    const save = setLayout([column('a'), column('b', 1)]);
    const state = new DeckState();

    expect(state.rowSplit()).toBe(0.5);

    state.setRowSplit(0.95);
    jest.runOnlyPendingTimers();

    expect(state.rowSplit()).toBe(0.8);
    expect(save).toHaveBeenLastCalledWith({ deckRowSplit: 0.8 });
  });

  it('starts from the saved split', () => {
    setLayout(null);
    app.session.user!.pushAttributes({ preferences: { deckRowSplit: 0.3 } });

    const state = new DeckState();

    expect(state.rowSplit()).toBe(0.3);
    expect(state.isCustomised()).toBe(true);
  });

  it('counts the neighbours a column is sized against', () => {
    setLayout([column('a'), column('b'), column('c', 1)]);
    const state = new DeckState();

    expect(state.neighbours('a', false).map((column) => column.config.id)).toEqual(['b']);
    expect(state.neighbours('a', true).map((column) => column.config.id)).toEqual(['b', 'c']);
  });
});

describe('resetting', () => {
  it('clears the stored layout and cancels a save still pending', () => {
    jest.useFakeTimers();
    const save = setLayout([column('a'), column('b')]);
    const state = new DeckState();

    state.moveTo('a', 0, 1);
    state.reset();
    jest.runOnlyPendingTimers();

    expect(save).toHaveBeenCalledTimes(1);
    expect(save).toHaveBeenCalledWith({ [PREFERENCE_KEY]: null, deckRowSplit: null });
    expect(state.isCustomised()).toBe(false);
    expect(state.columns().map((column) => column.config.type)).not.toContain('test');
  });
});

describe('sync', () => {
  it('picks up a layout saved elsewhere, keeping surviving columns’ state', () => {
    setLayout([column('a'), column('b')]);
    const state = new DeckState();
    const [a] = state.columns();

    a.scrollTop = 120;
    app.session.user!.pushAttributes({ preferences: { [PREFERENCE_KEY]: [column('a'), column('n')] } });
    state.sync();

    const columns = state.columns();

    expect(columns.map((column) => column.config.id)).toEqual(['a', 'n']);
    expect(columns[0]).toBe(a);
    expect(columns[0].scrollTop).toBe(120);
  });

  it('leaves the layout alone while the member’s own change is waiting to save', () => {
    jest.useFakeTimers();
    setLayout([column('a'), column('b')]);
    const state = new DeckState();

    state.moveTo('a', 0, 1);
    app.session.user!.pushAttributes({ preferences: { [PREFERENCE_KEY]: [column('z')] } });
    state.sync();

    expect(ids(state)).toEqual([['b', 'a'], []]);
  });
});

describe('realtime events', () => {
  function deckWith(...configs: ReturnType<typeof column>[]) {
    setLayout(configs);
    const state = new DeckState();
    state.columns();

    return state as DeckState & { onEvent(kind: 'public' | 'user', name: string, data: any): void };
  }

  it('hands each column the event with its post and discussion', () => {
    const state = deckWith(column('a'), column('b'));

    state.onEvent('public', POSTED, postEventPayload('101', '11'));

    for (const id of ['a', 'b']) {
      const [event] = sourceOf(id).events;

      expect(event.name).toBe(POSTED);
      expect(event.post?.id()).toBe('101');
      expect(event.discussion?.id()).toBe('11');
    }
  });

  // Realtime sends a public discussion's events on the public channel and a
  // personalised copy on the member's own channel.
  it('dispatches an event heard on both channels once, updating the store from the personal copy', () => {
    const state = deckWith(column('a'));
    const personal = postEventPayload('102', '12');
    personal.data.attributes.title = 'Personalised';

    state.onEvent('public', POSTED, postEventPayload('102', '12'));
    state.onEvent('user', POSTED, personal);

    expect(sourceOf('a').events).toHaveLength(1);
    expect(app.store.getById<any>('discussions', '12').title()).toBe('Personalised');
  });

  it('tells two replies in the same discussion apart', () => {
    const state = deckWith(column('a'));

    state.onEvent('public', POSTED, postEventPayload('103', '13'));
    state.onEvent('public', POSTED, postEventPayload('104', '13'));

    expect(sourceOf('a').events.map((event) => event.post?.id())).toEqual(['103', '104']);
  });

  it('dispatches the same event again once the duplicate window has passed', () => {
    jest.useFakeTimers();
    const state = deckWith(column('a'));

    state.onEvent('public', RENAMED, { data: discussionData('14') });
    jest.advanceTimersByTime(5000);
    state.onEvent('public', RENAMED, { data: discussionData('14') });

    expect(sourceOf('a').events).toHaveLength(2);
  });

  it('ignores pusher’s own events', () => {
    const state = deckWith(column('a'));

    state.onEvent('public', 'pusher:subscription_succeeded', { data: {} });

    expect(sourceOf('a').events).toHaveLength(0);
  });

  describe('removals', () => {
    // The public channel's copy is worked out for guests; a member gets their own.
    it('only takes a member’s removals from their own channel', () => {
      const state = deckWith(column('a'));

      state.onEvent('public', REMOVED, { data: { type: 'discussions', id: '15' } });

      expect(sourceOf('a').events).toHaveLength(0);
    });

    it('finds the removed post and its discussion in the store', () => {
      app.store.pushPayload({ data: discussionData('16'), included: [postData('106', '16')] } as any);
      const state = deckWith(column('a'));

      state.onEvent('user', REMOVED, { data: { type: 'posts', id: '106' }, meta: { discussionId: 16 } });

      const [event] = sourceOf('a').events;

      expect(event.name).toBe(REMOVED);
      expect(event.post?.id()).toBe('106');
      expect(event.discussion?.id()).toBe('16');
    });

    it('falls back to the discussion id in the meta for a post never loaded', () => {
      app.store.pushPayload({ data: discussionData('17') } as any);
      const state = deckWith(column('a'));

      state.onEvent('user', REMOVED, { data: { type: 'posts', id: '9999' }, meta: { discussionId: 17 } });

      const [event] = sourceOf('a').events;

      expect(event.post).toBeNull();
      expect(event.discussion?.id()).toBe('17');
    });

    it('does not put anything in the store', () => {
      const state = deckWith(column('a'));

      state.onEvent('user', REMOVED, { data: { type: 'discussions', id: '18' } });

      expect(app.store.getById('discussions', '18')).toBeUndefined();
      expect(sourceOf('a').events[0].discussion).toBeNull();
    });
  });
});
