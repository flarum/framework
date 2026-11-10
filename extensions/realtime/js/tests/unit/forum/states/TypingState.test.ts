import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

import TypingState from '../../../../src/forum/states/TypingState';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../../framework/core/js');

/** Mirrors the private EXPIRY_MS in TypingState. */
const EXPIRY_MS = 6000;

beforeAll(() => {
  const cwd = process.cwd();

  try {
    // bootstrap reads core's locale file relative to the working directory.
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

afterEach(() => {
  jest.useRealTimers();
});

/**
 * A `client-typing` payload as it arrives off the socket. `time` is stamped by
 * the *sender*, so `clockOffset` models how far that machine's clock sits from
 * ours: negative for a slow clock, positive for a fast one.
 */
function typingEvent(displayName: string, clockOffset = 0) {
  return { displayName, discloseOnline: true, time: Date.now() + clockOffset };
}

describe('TypingState', () => {
  it('registers a typer whose clock matches ours', () => {
    const state = new TypingState();

    state.add(typingEvent('Alice'));

    expect(Object.keys(state.active())).toEqual(['Alice']);

    state.dispose();
  });

  // Regression: expiry used to compare the sender's `data.time` against our own
  // `Date.now()`. A typer whose clock ran ~51s slow had every event arrive
  // already past EXPIRY_MS, so it was pruned on the first read and the
  // indicator never lit up — while the discussion-list dot, which stamps on
  // arrival, worked fine.
  it('registers a typer whose clock is far behind ours', () => {
    const state = new TypingState();

    state.add(typingEvent('Alice', -51_000));

    expect(Object.keys(state.active())).toEqual(['Alice']);

    state.dispose();
  });

  // The mirror image: a fast sender clock used to produce an entry that could
  // never expire, leaving them "typing" forever.
  it('expires a typer whose clock is far ahead of ours', () => {
    jest.useFakeTimers();

    const state = new TypingState();

    state.add(typingEvent('Alice', 51_000));
    jest.advanceTimersByTime(EXPIRY_MS + 1);

    expect(state.active()).toEqual({});

    state.dispose();
  });

  it('keeps a typer active until the expiry window elapses', () => {
    jest.useFakeTimers();

    const state = new TypingState();

    state.add(typingEvent('Alice'));
    jest.advanceTimersByTime(EXPIRY_MS - 1);

    expect(Object.keys(state.active())).toEqual(['Alice']);

    jest.advanceTimersByTime(2);

    expect(state.active()).toEqual({});

    state.dispose();
  });

  // Regression: the self-clearing redraw was scheduled `latestTime - Date.now()`
  // ms out — always negative, so it fired immediately and re-armed itself
  // through the view, spinning for the whole window instead of waiting it out.
  it('schedules the self-clearing redraw for the expiry, not for right away', () => {
    jest.useFakeTimers();

    const state = new TypingState();
    state.add(typingEvent('Alice'));

    const redraw = jest.spyOn(m, 'redraw').mockImplementation(() => {});

    try {
      state.active();

      jest.advanceTimersByTime(EXPIRY_MS - 100);
      expect(redraw).not.toHaveBeenCalled();

      jest.advanceTimersByTime(200);
      expect(redraw).toHaveBeenCalled();
    } finally {
      redraw.mockRestore();
      state.dispose();
    }
  });

  it('drops its pending timer when disposed', () => {
    jest.useFakeTimers();

    const state = new TypingState();
    state.add(typingEvent('Alice'));
    state.active();

    expect(jest.getTimerCount()).toBeGreaterThan(0);

    state.dispose();

    expect(jest.getTimerCount()).toBe(0);
  });
});
