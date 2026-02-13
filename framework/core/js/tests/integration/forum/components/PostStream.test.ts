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
