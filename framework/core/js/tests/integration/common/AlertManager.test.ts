import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { app } from '../../../src/forum';
import mq from 'mithril-query';
import AlertManager from '../../../src/common/components/AlertManager';

beforeAll(() => bootstrapForum());

describe('AlertManager', () => {
  beforeAll(() => app.boot());

  test('can show and dismiss an alert', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    const id = app.alerts.show({ type: 'success' }, 'Hello, world!');

    manager.redraw();

    expect(manager).toContainRaw('Hello, world!');

    app.alerts.dismiss(id);

    manager.redraw();

    expect(manager).not.toContainRaw('Hello, world!');
  });

  test('can clear all alerts', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.show({ type: 'success' }, 'Hello, world!');
    app.alerts.show({ type: 'error' }, 'Goodbye, world!');

    manager.redraw();

    expect(manager).toContainRaw('Hello, world!');
    expect(manager).toContainRaw('Goodbye, world!');

    app.alerts.clear();

    manager.redraw();

    expect(manager).not.toContainRaw('Hello, world!');
    expect(manager).not.toContainRaw('Goodbye, world!');
  });
});

describe('AlertManager loading indicator', () => {
  beforeAll(() => app.boot());

  beforeEach(() => {
    jest.useFakeTimers();
    app.alerts.clear();
  });

  afterEach(() => {
    jest.clearAllTimers();
    jest.useRealTimers();
    app.alerts.clear();
  });

  // The loading indicator renders the `core.ref.loading` translation. The test
  // translator emits the raw key reference rather than the resolved "Loading..."
  // string, so match against the key the indicator is built from.
  const LOADING_TOKEN = 'core.ref.loading';

  /** Number of loading indicators currently rendered. */
  const loadingCount = (manager: ReturnType<typeof mq>): number => {
    manager.redraw();
    const text: string = (manager.rootEl as HTMLElement).textContent ?? '';
    return (text.match(new RegExp(LOADING_TOKEN.replace(/\./g, '\\.'), 'g')) || []).length;
  };

  test('a fast load resolved before the delay never shows the indicator', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading();
    jest.advanceTimersByTime(150); // resolves before the 250ms threshold
    app.alerts.clearLoading();

    jest.advanceTimersByTime(500); // ensure the (cancelled) window has fully passed

    expect(loadingCount(manager)).toBe(0);
  });

  test('a slow load shows the indicator only after the 250ms delay', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading();

    jest.advanceTimersByTime(249);
    expect(loadingCount(manager)).toBe(0);

    jest.advanceTimersByTime(1);
    expect(loadingCount(manager)).toBe(1);

    app.alerts.clearLoading();
    expect(loadingCount(manager)).toBe(0);
  });

  test('multiple concurrent loads combine into a single indicator', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading();
    app.alerts.showLoading();
    app.alerts.showLoading();

    jest.advanceTimersByTime(250);

    expect(loadingCount(manager)).toBe(1);

    // Indicator persists until every outstanding load completes.
    app.alerts.clearLoading();
    expect(loadingCount(manager)).toBe(1);
    app.alerts.clearLoading();
    expect(loadingCount(manager)).toBe(1);
    app.alerts.clearLoading();
    expect(loadingCount(manager)).toBe(0);
  });

  test('staggered loads keep exactly one indicator', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading(); // A
    jest.advanceTimersByTime(250); // A shown
    expect(loadingCount(manager)).toBe(1);

    app.alerts.showLoading(); // B starts while A is showing
    jest.advanceTimersByTime(250);
    expect(loadingCount(manager)).toBe(1); // still a single combined indicator

    app.alerts.clearLoading(); // A done, B still running
    expect(loadingCount(manager)).toBe(1);

    app.alerts.clearLoading(); // B done
    expect(loadingCount(manager)).toBe(0);
  });

  test('a fast load amongst concurrent loads does not prematurely clear', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading(); // A
    app.alerts.showLoading(); // B
    app.alerts.clearLoading(); // A finishes before the threshold

    jest.advanceTimersByTime(250);

    expect(loadingCount(manager)).toBe(1); // B still loading -> indicator shows

    app.alerts.clearLoading(); // B done
    expect(loadingCount(manager)).toBe(0);
  });

  test('completing the load before the delay leaves no pending timer', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    app.alerts.showLoading();
    app.alerts.clearLoading();

    // A subsequent, unrelated slow load should still behave correctly.
    app.alerts.showLoading();
    jest.advanceTimersByTime(250);
    expect(loadingCount(manager)).toBe(1);

    app.alerts.clearLoading();
    expect(loadingCount(manager)).toBe(0);
  });

  test('finishing a load does not clear unrelated alerts', () => {
    const manager = mq(AlertManager, { state: app.alerts });

    const id = app.alerts.show({ type: 'success' }, 'Hello, world!');
    app.alerts.showLoading();
    jest.advanceTimersByTime(250);

    manager.redraw();
    expect(manager).toContainRaw('Hello, world!');
    expect(loadingCount(manager)).toBe(1);

    app.alerts.clearLoading();
    manager.redraw();
    expect(manager).toContainRaw('Hello, world!'); // unrelated alert survives
    expect(loadingCount(manager)).toBe(0);

    app.alerts.dismiss(id);
    manager.redraw();
    expect(manager).not.toContainRaw('Hello, world!');
  });
});
