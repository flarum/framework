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
