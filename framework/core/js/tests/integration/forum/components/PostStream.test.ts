import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import PostStream from '../../../../src/forum/components/PostStream';
import PostStreamState from '../../../../src/forum/states/PostStreamState';
import Discussion from '../../../../src/common/models/Discussion';
import app from '../../../../src/forum/app';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

// Provide a minimal ResizeObserver stub for JSDOM environments where it
// is not natively available, so that PostStream.oncreate can call
// setupScrollAnchoring without throwing.
if (typeof globalThis.ResizeObserver === 'undefined') {
  // @ts-ignore
  globalThis.ResizeObserver = class ResizeObserver {
    observe() {}
    unobserve() {}
    disconnect() {}
    constructor(_cb: any) {}
  };
}

beforeAll(() => bootstrapForum());

describe('PostStream component', () => {
  app.store.pushPayload({
    data: {
      id: '1',
      type: 'discussions',
      attributes: {
        title: 'Discussion title',
      },
      relationships: {
        posts: {
          data: [
            { id: '1', type: 'posts' },
            { id: '2', type: 'posts' },
            { id: '3', type: 'posts' },
            { id: '4', type: 'posts' },
          ],
        },
      },
    },
  });

  app.store.pushPayload({
    data: [
      {
        id: '1',
        type: 'posts',
        attributes: { contentType: 'comment', canEdit: false, createdAt: new Date(), contentHtml: '<strong>Hi</strong>' },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '2',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Bye</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '3',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Hi again</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '4',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Bye again</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
    ],
  });

  const discussion = app.store.getById<Discussion>('discussions', '1');

  it('renders correctly', () => {
    app.boot();

    const postStream = mq(PostStream, {
      stream: new PostStreamState(discussion, app.store.all('posts')),
      discussion,
    });

    expect(postStream).toContainRaw('Hi');
    expect(postStream).toContainRaw('Bye');
    expect(postStream).toContainRaw('Hi again');
    expect(postStream).toContainRaw('Bye again');
  });
});

describe('PostStream scroll anchoring (settling guard)', () => {
  let mockObserveCallbacks: Function[];
  let mockDisconnect: ReturnType<typeof jest.fn>;
  let mockObserve: ReturnType<typeof jest.fn>;
  let originalResizeObserver: typeof globalThis.ResizeObserver;

  beforeEach(() => {
    mockObserveCallbacks = [];
    mockDisconnect = jest.fn();
    mockObserve = jest.fn();

    originalResizeObserver = globalThis.ResizeObserver;

    // @ts-ignore - mock ResizeObserver for JSDOM
    globalThis.ResizeObserver = jest.fn((callback: Function) => {
      mockObserveCallbacks.push(callback);
      return {
        observe: mockObserve,
        unobserve: jest.fn(),
        disconnect: mockDisconnect,
      };
    }) as any;
  });

  afterEach(() => {
    globalThis.ResizeObserver = originalResizeObserver;
  });

  it('creates a ResizeObserver when setupScrollAnchoring is called', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    instance.setupScrollAnchoring();

    expect(globalThis.ResizeObserver).toHaveBeenCalledTimes(1);
    expect(instance.resizeObserver).toBeDefined();
    expect(instance.observedElements).toBeInstanceOf(Set);
  });

  it('disconnects the ResizeObserver when cleanupScrollAnchoring is called', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    instance.setupScrollAnchoring();
    instance.cleanupScrollAnchoring();

    expect(mockDisconnect).toHaveBeenCalledTimes(1);
    expect(instance.resizeObserver).toBeNull();
    expect(instance.observedElements).toBeNull();
  });

  it('observes new PostStream-item elements added to the DOM', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.getMarginTop = () => 0;

    const container = document.createElement('div');
    const item1 = document.createElement('div');
    item1.className = 'PostStream-item';
    const item2 = document.createElement('div');
    item2.className = 'PostStream-item';
    container.appendChild(item1);
    container.appendChild(item2);
    instance.element = container;

    instance.setupScrollAnchoring();

    expect(mockObserve).toHaveBeenCalledTimes(2);
    expect(instance.observedElements!.size).toBe(2);

    const item3 = document.createElement('div');
    item3.className = 'PostStream-item';
    container.appendChild(item3);

    instance.observeNewPostItems();

    expect(mockObserve).toHaveBeenCalledTimes(3);
    expect(instance.observedElements!.size).toBe(3);
  });

  it('re-scrolls to target post when ResizeObserver fires during settling', () => {
    jest.useFakeTimers();
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.getMarginTop = () => 60;
    instance.calculatePosition = jest.fn();
    const container = document.createElement('div');
    const item = document.createElement('div');
    item.className = 'PostStream-item';
    item.setAttribute('data-number', '5');
    container.appendChild(item);
    instance.element = container;

    instance.setupScrollAnchoring();
    instance.settling = true;
    instance.settlingTarget = { number: 5 };
    instance.$ = jest.fn((sel: string) => {
      if (sel === '.PostStream-item[data-number=5]') {
        return { length: 1, is: () => false, offset: () => ({ top: 1000 }), outerHeight: () => 100 };
      }
      return { length: 0 };
    });

    const callback = mockObserveCallbacks[0];
    callback([]);

    // Verify the target element was queried for offset calculation
    expect(instance.$).toHaveBeenCalledWith('.PostStream-item[data-number=5]');

    // Verify the stability timer was reset (endSettling will fire after 3s)
    jest.advanceTimersByTime(3000);
    expect(instance.settling).toBe(false);
    expect(instance.calculatePosition).toHaveBeenCalled();

    jest.useRealTimers();
  });

  it('does not re-scroll when ResizeObserver fires outside of settling', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.reScrollToTarget = jest.fn();

    instance.setupScrollAnchoring();

    // settling is false by default after setup
    expect(instance.settling).toBe(false);

    const callback = mockObserveCallbacks[0];
    callback([]);

    expect(instance.reScrollToTarget).not.toHaveBeenCalled();
  });

  it('exits settling mode on user interaction', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.calculatePosition = jest.fn();

    instance.setupScrollAnchoring();
    instance.settling = true;
    instance.settlingTarget = { number: 1 };
    instance.startSettlingListeners();

    expect(instance.settling).toBe(true);
    window.dispatchEvent(new WheelEvent('wheel', { bubbles: true }));

    expect(instance.settling).toBe(false);
    expect(instance.settlingTarget).toBeNull();
    expect(instance.calculatePosition).toHaveBeenCalledTimes(1);
  });

  it('exits settling mode after stability timeout', () => {
    jest.useFakeTimers();
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.calculatePosition = jest.fn();

    instance.setupScrollAnchoring();
    instance.settling = true;
    instance.settlingTarget = { number: 1 };
    instance.resetStabilityTimer();

    expect(instance.settling).toBe(true);
    jest.advanceTimersByTime(3000);

    expect(instance.settling).toBe(false);
    expect(instance.calculatePosition).toHaveBeenCalledTimes(1);
    jest.useRealTimers();
  });
});

describe('PostStream position sync (immediate emit + settling lifecycle)', () => {
  let originalResizeObserver: typeof globalThis.ResizeObserver;

  beforeEach(() => {
    originalResizeObserver = globalThis.ResizeObserver;
    // @ts-ignore
    globalThis.ResizeObserver = jest.fn(() => ({
      observe: jest.fn(),
      unobserve: jest.fn(),
      disconnect: jest.fn(),
    }));
  });

  afterEach(() => {
    globalThis.ResizeObserver = originalResizeObserver;
  });

  it('emits position on programmatic jump without waiting for the stability timer', () => {
    jest.useFakeTimers();

    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    const onPositionChange = jest.fn();
    instance.attrs = { onPositionChange };

    instance.computeVisiblePosition = jest.fn(() => ({ startNumber: 3, endNumber: 3 }));
    instance.setupScrollAnchoring();

    instance.calculatePosition(0, { fallbackToTarget: true });

    expect(onPositionChange).toHaveBeenCalledTimes(1);
    expect(onPositionChange).toHaveBeenCalledWith(3, 3, 3);

    // Stability timer also triggers calculatePosition (reconciliation).
    instance.calculatePosition = jest.fn();
    instance.settling = true;
    instance.settlingTarget = { number: 3 };
    instance.resetStabilityTimer();
    jest.advanceTimersByTime(3000);

    expect(instance.calculatePosition).toHaveBeenCalledTimes(1);

    jest.useRealTimers();
  });

  it('does not emit via onscroll while settling', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false, pagesLoading: 0 };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.updateScrubber = jest.fn();
    instance.loadPostsIfNeeded = jest.fn();
    instance.calculatePosition = jest.fn();

    instance.settling = true;

    instance.onscroll(0);

    expect(instance.calculatePosition).not.toHaveBeenCalled();
  });

  it('endSettling calls calculatePosition for reconciliation', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.calculatePosition = jest.fn();

    instance.setupScrollAnchoring();
    instance.settling = true;
    instance.settlingTarget = { number: 1 };
    instance.startSettlingListeners();

    instance.endSettling();

    expect(instance.settling).toBe(false);
    expect(instance.calculatePosition).toHaveBeenCalledTimes(1);
  });

  it('does not emit position when cleanupScrollAnchoring is called while settling', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    const onPositionChange = jest.fn();
    instance.attrs = { onPositionChange };

    instance.setupScrollAnchoring();
    instance.settling = true;
    instance.settlingTarget = { number: 5 };
    instance.startSettlingListeners();

    instance.cleanupScrollAnchoring();

    expect(onPositionChange).not.toHaveBeenCalled();
    expect(instance.settling).toBe(false);
  });

  it('does not leak wheel listeners on consecutive beginSettling calls', () => {
    jest.useFakeTimers();

    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;
    instance.calculatePosition = jest.fn();

    const addSpy = jest.spyOn(window, 'addEventListener');
    const removeSpy = jest.spyOn(window, 'removeEventListener');

    instance.setupScrollAnchoring();

    instance.beginSettling({ number: 1 });
    const wheelAddsAfterFirst = addSpy.mock.calls.filter((c) => c[0] === 'wheel').length;

    instance.beginSettling({ number: 2 });
    const wheelAddsAfterSecond = addSpy.mock.calls.filter((c) => c[0] === 'wheel').length;
    const wheelRemovesAfterSecond = removeSpy.mock.calls.filter((c) => c[0] === 'wheel').length;

    expect(wheelAddsAfterSecond - wheelAddsAfterFirst).toBe(1);
    expect(wheelRemovesAfterSecond).toBeGreaterThanOrEqual(1);

    const netListeners = wheelAddsAfterSecond - wheelRemovesAfterSecond;
    expect(netListeners).toBe(1);

    instance.stopSettlingListeners();
    addSpy.mockRestore();
    removeSpy.mockRestore();

    jest.useRealTimers();
  });

  it('falls back to settlingTarget.number when all visible items are loading placeholders', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    const onPositionChange = jest.fn();
    instance.attrs = { onPositionChange };

    instance.computeVisiblePosition = jest.fn(() => ({ startNumber: undefined, endNumber: undefined }));
    instance.settlingTarget = { number: 7 };

    instance.calculatePosition(0, { fallbackToTarget: true });

    expect(onPositionChange).toHaveBeenCalledWith(7, undefined, 7);
  });

  it('skips emit for reply targets when no loaded post is visible', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false };
    instance.element = document.createElement('div');
    instance.getMarginTop = () => 0;

    const onPositionChange = jest.fn();
    instance.attrs = { onPositionChange };

    instance.computeVisiblePosition = jest.fn(() => ({ startNumber: undefined, endNumber: undefined }));
    instance.settlingTarget = { index: 99, reply: true };

    instance.calculatePosition(0, { fallbackToTarget: true });

    expect(onPositionChange).not.toHaveBeenCalled();
  });

  it('resolveTargetStartNumber returns the post number for number targets', () => {
    const instance = new (PostStream as any)();
    instance.settlingTarget = { number: 42 };
    instance.$ = jest.fn();

    expect(instance.resolveTargetStartNumber()).toBe(42);
  });

  it('resolveTargetStartNumber reads data-number from DOM for index targets', () => {
    const instance = new (PostStream as any)();
    instance.settlingTarget = { index: 5 };
    instance.$ = jest.fn(() => ({ data: () => 10 }));

    expect(instance.resolveTargetStartNumber()).toBe(10);
  });

  it('resolveTargetStartNumber returns undefined for index targets pointing to unloaded posts', () => {
    const instance = new (PostStream as any)();
    instance.settlingTarget = { index: 5 };
    instance.$ = jest.fn(() => ({ data: () => undefined }));

    expect(instance.resolveTargetStartNumber()).toBeUndefined();
  });

  it('resolveTargetStartNumber returns undefined for reply targets', () => {
    const instance = new (PostStream as any)();
    instance.settlingTarget = { index: 99, reply: true };
    instance.$ = jest.fn();

    expect(instance.resolveTargetStartNumber()).toBeUndefined();
  });

  it('resolveTargetStartNumber returns undefined when settlingTarget is null', () => {
    const instance = new (PostStream as any)();
    instance.settlingTarget = null;

    expect(instance.resolveTargetStartNumber()).toBeUndefined();
  });
});
