import { jest } from '@jest/globals';
import DeckColumnState from '../../../../src/forum/states/DeckColumnState';
import { POSTED, RENAMED, STARTED } from '../../../../src/forum/columns/realtimeEvents';
import { RecordingSource, boot, column, registerTestTypes } from '../helpers';

/** Mirrors DeckColumnState's CHECK_DEBOUNCE and DeckState's MIN_CHECK_INTERVAL. */
const CHECK_DEBOUNCE = 3000;
const MIN_CHECK_INTERVAL = 15000;

beforeAll(() => {
  boot();
  registerTestTypes();
});

beforeEach(() => {
  jest.useFakeTimers();
  // No jitter, so timings are exact.
  jest.spyOn(Math, 'random').mockReturnValue(0);
});

afterEach(() => {
  jest.restoreAllMocks();
  jest.useRealTimers();
});

let counter = 0;

function visibleColumn(): DeckColumnState & { source: RecordingSource } {
  const state = new DeckColumnState(column(`c${++counter}`)) as DeckColumnState & { source: RecordingSource };
  state.visible = true;

  return state;
}

const event = (name: string) => ({ name, payload: {}, model: null, discussion: null, post: null });

/** Lets the check's promise chain settle under fake timers. */
const settle = () =>
  Promise.resolve()
    .then(() => Promise.resolve())
    .then(() => Promise.resolve());

describe('handling realtime events', () => {
  it('only checks with the server when the source asks', () => {
    const state = visibleColumn();

    state.source.result = 'updated';
    state.handleRealtime(event(RENAMED), true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);

    expect(state.source.checkForNew).not.toHaveBeenCalled();
  });

  it('keeps the place of items inserted above the reader, once', () => {
    const state = visibleColumn();

    state.source.result = 'inserted';
    state.handleRealtime(event(POSTED), true);

    expect(state.consumeTopInsert()).toBe(true);
    expect(state.consumeTopInsert()).toBe(false);
  });

  it('checks a source without its own handler on new discussions and posts only', async () => {
    const state = visibleColumn();
    (state.source as any).onRealtime = undefined;

    state.handleRealtime(event(RENAMED), false);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);
    await settle();
    expect(state.source.checkForNew).not.toHaveBeenCalled();

    state.handleRealtime(event(STARTED), false);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);
    await settle();
    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);
  });
});

describe('checking', () => {
  it('marks an off-screen column stale instead of asking the server', () => {
    const state = visibleColumn();
    state.visible = false;

    state.queueCheck(true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);

    expect(state.stale).toBe(true);
    expect(state.source.checkForNew).not.toHaveBeenCalled();
  });

  it('settles a burst of events into one check', async () => {
    const state = visibleColumn();
    state.source.checkForNew.mockResolvedValue(4);

    // Events keep arriving while the first is settling.
    for (let i = 0; i < 5; i++) {
      state.queueCheck(false);
      jest.advanceTimersByTime(500);
    }

    jest.advanceTimersByTime(CHECK_DEBOUNCE - 2500 - 1);
    expect(state.source.checkForNew).not.toHaveBeenCalled();

    jest.advanceTimersByTime(1);
    await settle();
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL);
    await settle();

    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);
    expect(state.newCount).toBe(4);
  });

  it('asks the server at most once per interval', async () => {
    const state = visibleColumn();

    state.queueCheck(false);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    state.queueCheck(false);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL - 1);
    await settle();
    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(1);
    await settle();
    expect(state.source.checkForNew).toHaveBeenCalledTimes(2);
  });

  it('marks the column stale when the tab is hidden by the time the check is due', () => {
    const state = visibleColumn();
    const hidden = jest.spyOn(document, 'hidden', 'get').mockReturnValue(true);

    state.queueCheck(false);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);

    expect(state.stale).toBe(true);
    expect(state.source.checkForNew).not.toHaveBeenCalled();
    hidden.mockRestore();
  });

  it('can be cancelled', () => {
    const state = visibleColumn();

    state.queueCheck(false);
    state.cancelCheck();
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);

    expect(state.source.checkForNew).not.toHaveBeenCalled();
  });

  describe('while live', () => {
    it('puts new items straight in when the source can', async () => {
      const state = visibleColumn();
      state.newCount = 3;
      state.source.applyNew = jest.fn(() => Promise.resolve(2));

      await state.check(true);

      expect(state.newCount).toBe(0);
      expect(state.consumeTopInsert()).toBe(true);
      expect(state.source.checkForNew).not.toHaveBeenCalled();
    });

    it('falls back to counting when the source can’t place them', async () => {
      const state = visibleColumn();
      state.source.applyNew = jest.fn(() => Promise.resolve(null));
      state.source.checkForNew.mockResolvedValue(5);

      await state.check(true);

      expect(state.newCount).toBe(5);
    });
  });

  it('waits behind the "new" pill when not live, even if the source could insert', async () => {
    const state = visibleColumn();
    state.source.applyNew = jest.fn(() => Promise.resolve(2));
    state.source.checkForNew.mockResolvedValue(2);

    await state.check(false);

    expect(state.source.applyNew).not.toHaveBeenCalled();
    expect(state.newCount).toBe(2);
  });
});

it('prunes before every check', async () => {
  const state = visibleColumn();
  const prune = jest.fn();
  (state.source as any).prune = prune;

  await state.check(false);

  expect(prune).toHaveBeenCalled();
});

// Locking, stickying or re-tagging a discussion moves it in or out of a
// filter without any new activity, so the column re-asks about that one.
describe('re-asking about particular items', () => {
  function rechecking(state: DeckColumnState & { source: RecordingSource }, inserted = false) {
    const recheck = jest.fn(() => Promise.resolve(inserted));
    (state.source as any).recheck = recheck;

    return recheck;
  }

  it('asks only about them when that is all a burst of events needs', async () => {
    const state = visibleColumn();
    const recheck = rechecking(state);
    state.source.applyNew = jest.fn(() => Promise.resolve(0));

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    state.handleRealtime(event('stickiedEvent'), true);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    expect(recheck).toHaveBeenCalledTimes(1);
    expect(state.source.applyNew).not.toHaveBeenCalled();
    expect(state.source.checkForNew).not.toHaveBeenCalled();
  });

  it('runs the full check, re-asking too, when another event in the burst needs it', async () => {
    const state = visibleColumn();
    const recheck = rechecking(state);
    state.source.applyNew = jest.fn(() => Promise.resolve(0));

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    state.source.result = 'check';
    state.handleRealtime(event(POSTED), true);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    expect(recheck).toHaveBeenCalledTimes(1);
    expect(state.source.applyNew).toHaveBeenCalledTimes(1);
  });

  it('re-asks before every check', async () => {
    const state = visibleColumn();
    const recheck = rechecking(state);

    await state.check(false);

    expect(recheck).toHaveBeenCalledTimes(1);
    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);
  });

  it('keeps the reader’s place when an item arrives at the top', async () => {
    const state = visibleColumn();
    rechecking(state, true);

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    expect(state.consumeTopInsert()).toBe(true);
  });

  it('counts towards the interval between requests', async () => {
    const state = visibleColumn();
    const recheck = rechecking(state);

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL - 1);
    await settle();
    expect(recheck).toHaveBeenCalledTimes(1);

    jest.advanceTimersByTime(1);
    await settle();
    expect(recheck).toHaveBeenCalledTimes(2);
  });

  it('marks an off-screen column stale instead', () => {
    const state = visibleColumn();
    const recheck = rechecking(state);
    state.visible = false;

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL * 2);

    expect(state.stale).toBe(true);
    expect(recheck).not.toHaveBeenCalled();
  });

  // An answer arriving late must not undo a newer one: locked, then unlocked.
  it('waits for one to finish before asking again', async () => {
    const state = visibleColumn();
    const calls: Array<() => void> = [];
    const recheck = jest.fn(() => new Promise<boolean>((resolve) => calls.push(() => resolve(false))));
    (state.source as any).recheck = recheck;

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();
    expect(recheck).toHaveBeenCalledTimes(1);

    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL);
    await settle();
    expect(recheck).toHaveBeenCalledTimes(1);

    calls[0]();
    await settle();
    await settle();
    expect(recheck).toHaveBeenCalledTimes(2);
  });

  // Joining at the top moves the column's activity key on; asking for
  // activity afterwards would skip replies older than the newcomer.
  it('asks for activity before re-asking', async () => {
    const state = visibleColumn();
    const order: string[] = [];
    (state.source as any).recheck = jest.fn(() => {
      order.push('recheck');

      return Promise.resolve(false);
    });
    state.source.applyNew = jest.fn(() => {
      order.push('applyNew');

      return Promise.resolve(0);
    });

    await state.check(true);

    expect(order).toEqual(['applyNew', 'recheck']);
  });

  // An activity answer arriving after a newer recheck could put back what
  // the recheck took out.
  it('re-asks only once a running check has its answer', async () => {
    const state = visibleColumn();
    const recheck = rechecking(state);
    let answer!: () => void;
    state.source.applyNew = jest.fn(() => new Promise<number | null>((resolve) => (answer = () => resolve(0))));

    const checking = state.check(true);
    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), true);
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL);
    await settle();
    expect(recheck).not.toHaveBeenCalled();

    answer();
    await checking;
    await settle();
    await settle();
    expect(recheck).toHaveBeenCalledTimes(2);
  });

  it('checks again afterwards when asked to while a check runs', async () => {
    const state = visibleColumn();
    rechecking(state);
    const answers: Array<() => void> = [];
    state.source.applyNew = jest.fn(() => new Promise<number | null>((resolve) => answers.push(() => resolve(0))));

    const first = state.check(true);
    state.check(true);
    await settle();
    answers[0]();
    await first;

    jest.advanceTimersByTime(MIN_CHECK_INTERVAL);
    await settle();
    expect(state.source.applyNew).toHaveBeenCalledTimes(2);
  });

  it('runs the full check for a source that can’t re-ask', async () => {
    const state = visibleColumn();

    state.source.result = 'recheck';
    state.handleRealtime(event('lockedEvent'), false);
    jest.advanceTimersByTime(CHECK_DEBOUNCE);
    await settle();

    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);
  });
});

describe('a column replaced by new settings', () => {
  it('asks nothing more, even a check it had already been asked to run again', async () => {
    const state = visibleColumn();
    let finish!: (count: number) => void;
    state.source.checkForNew.mockImplementation(() => new Promise<number>((resolve) => (finish = resolve)));

    state.check(false);
    await settle();
    // Something arrives while that check is running.
    state.check(false);
    state.discard();

    finish(0);
    // The running check's whole chain, through to its finally.
    for (let i = 0; i < 20; i++) await Promise.resolve();
    jest.advanceTimersByTime(MIN_CHECK_INTERVAL + CHECK_DEBOUNCE);
    for (let i = 0; i < 20; i++) await Promise.resolve();

    expect(state.source.checkForNew).toHaveBeenCalledTimes(1);
  });
});
