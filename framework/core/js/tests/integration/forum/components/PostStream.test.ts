import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import PostStream from '../../../../src/forum/components/PostStream';
import PostStreamState from '../../../../src/forum/states/PostStreamState';
import Discussion from '../../../../src/common/models/Discussion';
import Post from '../../../../src/common/models/Post';
import ItemList from '../../../../src/common/utils/ItemList';
import { extend } from '../../../../src/common/extend';
import app from '../../../../src/forum/app';
import m from 'mithril';
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

  it('looks ahead in the direction the reader scrolls, once a jump has settled', () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false, pagesLoading: 0, scrollAdjustment: 0 };
    instance.element = document.createElement('div');
    instance.updateScrubber = jest.fn();
    instance.loadPostsIfNeeded = jest.fn();
    instance.calculatePosition = jest.fn();

    instance.settling = true;
    instance.onscroll(100);
    instance.onscroll(300);
    instance.settling = false;
    instance.onscroll(400);
    instance.onscroll(350);
    instance.onscroll(350);

    expect((instance.loadPostsIfNeeded as jest.Mock).mock.calls).toEqual([
      [100, 0],
      [300, 0],
      [400, 1],
      [350, -1],
      [350, -1],
    ]);
  });

  it("does not count the stream's own scroll adjustments as the reader scrolling", () => {
    const instance = new (PostStream as any)();
    instance.stream = { paused: false, pagesLoading: 0, scrollAdjustment: 0 };
    instance.element = document.createElement('div');
    instance.updateScrubber = jest.fn();
    instance.loadPostsIfNeeded = jest.fn();
    instance.calculatePosition = jest.fn();

    instance.onscroll(10000);
    instance.onscroll(9900);
    // A page loaded above the reader, and the stream kept them in place.
    instance.stream.scrollAdjustment += 4100;
    instance.onscroll(14000);

    expect((instance.loadPostsIfNeeded as jest.Mock).mock.calls[2]).toEqual([14000, -1]);
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

describe('PostStream.afterPostItems', () => {
  /**
   * The store is shared and never reset, so every discussion here claims its
   * own id range. Seeding happens inside the tests rather than in the describe
   * body, because describe bodies all run before any test does -- pushing at
   * that point would put these posts into `app.store.all('posts')` for the
   * tests further up the file.
   */
  let nextId = 100;

  function seed(postCount: number, loadedOffsets: number[], withFirstPost = false) {
    const base = nextId;
    nextId += postCount + 1;

    const discussionId = String(base);
    const postIds = Array.from({ length: postCount }, (_, i) => String(base + 1 + i));

    app.store.pushPayload({
      data: {
        id: discussionId,
        type: 'discussions',
        attributes: { title: 'Discussion title' },
        relationships: {
          posts: { data: postIds.map((id) => ({ id, type: 'posts' })) },
          ...(withFirstPost ? { firstPost: { data: { id: postIds[0], type: 'posts' } } } : {}),
        },
      },
    });

    app.store.pushPayload({
      data: loadedOffsets.map((offset) => ({
        id: postIds[offset],
        type: 'posts',
        attributes: {
          number: offset + 1,
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: `<p>Post ${postIds[offset]}</p>`,
        },
        relationships: { discussion: { data: { id: discussionId, type: 'discussions' } } },
      })),
    });

    const discussion = app.store.getById<Discussion>('discussions', discussionId)!;
    const stream = new PostStreamState(discussion, []);

    // `show()` derives the window from the posts it is handed, which cannot
    // describe a hole. Setting it directly is what leaves the unloaded posts
    // in view as placeholders.
    stream.reset(0, postCount);

    return { discussion, stream, postIds };
  }

  function render(discussion: Discussion, stream: PostStreamState) {
    return mq(PostStream, { discussion, stream });
  }

  const original = PostStream.prototype.afterPostItems;

  afterEach(() => {
    PostStream.prototype.afterPostItems = original;
  });

  it('adds nothing to the stream when no extension has extended it', () => {
    const { discussion, stream } = seed(3, [0, 1, 2]);

    expect(render(discussion, stream)).not.toHaveElement('.PostStream-afterPost');
  });

  it('returns an empty ItemList by default', () => {
    const { discussion } = seed(1, [0]);
    const post = app.store.getById<Post>('posts', discussion.postIds()[0])!;
    const items = PostStream.prototype.afterPostItems.call(null, post, 0);

    expect(items).toBeInstanceOf(ItemList);
    expect(items.toArray()).toHaveLength(0);
  });

  it('renders an extension item after every loaded post', () => {
    extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>) {
      items.add('advert', m('div', { className: 'TestAfterPost' }, 'An advert'));
    });

    const { discussion, stream } = seed(3, [0, 1, 2]);
    const rendered = render(discussion, stream);

    expect(rendered.find('.PostStream-afterPost')).toHaveLength(3);
    expect(rendered.find('.TestAfterPost')).toHaveLength(3);
    expect(rendered).toContainRaw('An advert');
  });

  it('is passed the post and its index in the discussion', () => {
    const seen: Array<[string, number]> = [];

    extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>, post: Post, index: number) {
      seen.push([post.id()!, index]);
    });

    const { discussion, stream, postIds } = seed(3, [0, 1, 2]);
    render(discussion, stream);

    expect(seen).toEqual([
      [postIds[0], 0],
      [postIds[1], 1],
      [postIds[2], 2],
    ]);
  });

  it('numbers the index from the discussion, not from the loaded page', () => {
    const seen: number[] = [];

    extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>, post: Post, index: number) {
      seen.push(index);
    });

    const { discussion, stream } = seed(6, [3, 4, 5]);
    // The window starts part-way into the discussion, which is what makes the
    // two numbering schemes disagree.
    stream.reset(3, 6);
    render(discussion, stream);

    expect(seen).toEqual([3, 4, 5]);
  });

  it('is not called for posts that have not loaded yet', () => {
    // Recorded without dereferencing: `extend` runs the callback inside a
    // try/catch, so a `post.id()` that threw on a placeholder would be
    // swallowed and leave this list looking correct.
    const seen: Array<Post | null | undefined> = [];

    extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>, post: Post | null) {
      seen.push(post);
      items.add('advert', m('div', { className: 'TestAfterPost' }, 'An advert'));
    });

    const { discussion, stream, postIds } = seed(4, [0, 2]);
    const rendered = render(discussion, stream);

    expect(seen.map((post) => post?.id() ?? null)).toEqual([postIds[0], postIds[2]]);
    expect(rendered.find('.PostStream-afterPost')).toHaveLength(2);
  });

  it('renders alongside afterFirstPostItems rather than displacing it', () => {
    extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>) {
      items.add('advert', m('div', { className: 'TestAfterPost' }, 'An advert'));
    });

    const originalFirst = PostStream.prototype.afterFirstPostItems;
    extend(PostStream.prototype, 'afterFirstPostItems', function (items: ItemList<any>) {
      items.add('blurb', m('div', { className: 'TestAfterFirstPost' }, 'A blurb'));
    });

    try {
      const { discussion, stream } = seed(2, [0, 1], true);
      const rendered = render(discussion, stream);

      expect(rendered.find('.PostStream-afterFirstPost')).toHaveLength(1);
      expect(rendered.find('.PostStream-afterPost')).toHaveLength(2);
      expect(rendered).toContainRaw('A blurb');
      expect(rendered).toContainRaw('An advert');
    } finally {
      PostStream.prototype.afterFirstPostItems = originalFirst;
    }
  });

  it('only has article children in the feed, including the reply placeholder', () => {
    const { discussion, stream } = seed(2, [0, 1]);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);
    const rendered = render(discussion, stream);

    expect(rendered).toHaveElement('.ReplyPlaceholder');

    expect(rendered).toHaveElement('.PostStream[role=feed]');

    const children = rendered.find('.PostStream > *') as any[];
    expect(children.length).toBeGreaterThan(0);
    expect(children.every((child: any) => child.getAttribute('role') === 'article')).toBe(true);
  });
});

describe('PostStream reply placeholder', () => {
  let nextId = 500;

  function seed(postCount = 2) {
    const base = nextId;
    nextId += postCount + 1;

    const discussionId = String(base);
    const postIds = Array.from({ length: postCount }, (_, i) => String(base + 1 + i));

    app.store.pushPayload({
      data: {
        id: discussionId,
        type: 'discussions',
        attributes: { title: 'Discussion title' },
        relationships: { posts: { data: postIds.map((id) => ({ id, type: 'posts' })) } },
      },
    });

    app.store.pushPayload({
      data: postIds.map((id, i) => ({
        id,
        type: 'posts',
        attributes: { number: i + 1, contentType: 'comment', canEdit: false, createdAt: new Date(), contentHtml: `<p>Post ${id}</p>` },
        relationships: { discussion: { data: { id: discussionId, type: 'discussions' } } },
      })),
    });

    const discussion = app.store.getById<Discussion>('discussions', discussionId)!;
    const stream = new PostStreamState(discussion, []);
    stream.reset(0, postCount);

    return { discussion, stream };
  }

  function endItemsOf(discussion: Discussion, stream: PostStreamState): ItemList<any> {
    const instance = new (PostStream as any)();
    instance.discussion = discussion;
    instance.stream = stream;

    return instance.endItems();
  }

  const originalEndItems = PostStream.prototype.endItems;
  let originalUser: any;

  beforeEach(() => {
    originalUser = app.session.user;
  });

  afterEach(() => {
    PostStream.prototype.endItems = originalEndItems;
    app.session.user = originalUser;
    jest.restoreAllMocks();
  });

  it('renders the placeholder in a trailing article wrapper, as themes expect', () => {
    const { discussion, stream } = seed(2);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);

    const rendered = render(discussion, stream);
    const children = rendered.find('.PostStream > *') as any[];
    const last = children[children.length - 1];

    expect(children).toHaveLength(3);
    // The wrapper's oncreate fade-in is part of the default markup too.
    expect(last.className).toBe('PostStream-item fadeIn');
    expect(last.getAttribute('role')).toBe('article');
    expect(last.getAttribute('data-index')).toBe('2');
    expect(last.children).toHaveLength(1);
    expect(last.children[0].classList.contains('ReplyPlaceholder')).toBe(true);
    expect(rendered.find('.ReplyPlaceholder')).toHaveLength(1);
  });

  it('adds the placeholder to endItems as "reply" at priority -100', () => {
    const { discussion, stream } = seed(2);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);

    const items = endItemsOf(discussion, stream);

    expect(items.has('reply')).toBe(true);
    expect(items.getPriority('reply')).toBe(-100);
    expect(items.get('reply').key).toBe('reply');
    expect(items.get('reply').attrs['data-index']).toBe(2);
  });

  it('offers the placeholder to guests', () => {
    const { discussion, stream } = seed(2);
    app.session.user = null;
    jest.spyOn(discussion, 'canReply').mockReturnValue(false);

    expect(endItemsOf(discussion, stream).has('reply')).toBe(true);
  });

  it('leaves the placeholder out for a signed-in user who cannot reply', () => {
    const { discussion, stream } = seed(2);
    app.session.user = { id: () => '1' } as any;
    jest.spyOn(discussion, 'canReply').mockReturnValue(false);

    expect(endItemsOf(discussion, stream).has('reply')).toBe(false);
    expect(render(discussion, stream)).not.toHaveElement('.ReplyPlaceholder');
  });

  it('renders extension items at the default priority above the placeholder and lower ones below it', () => {
    const { discussion, stream } = seed(2);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);

    extend(PostStream.prototype, 'endItems', function (items: ItemList<any>) {
      items.add('above', m('div', { className: 'PostStream-item TestAbove', key: 'above', role: 'article' }));
      items.add('below', m('div', { className: 'PostStream-item TestBelow', key: 'below', role: 'article' }), -200);
    });

    const children = render(discussion, stream).find('.PostStream > *') as any[];
    const order = children.slice(-3).map((child: any) =>
      child.classList.contains('TestAbove') ? 'above' : child.classList.contains('TestBelow') ? 'below' : child.querySelector('.ReplyPlaceholder') ? 'reply' : '?'
    );

    expect(order).toEqual(['above', 'reply', 'below']);
  });

  it('lets an extension remove the placeholder', () => {
    const { discussion, stream } = seed(2);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);

    extend(PostStream.prototype, 'endItems', function (items: ItemList<any>) {
      items.remove('reply');
    });

    expect(render(discussion, stream)).not.toHaveElement('.ReplyPlaceholder');
  });

  it('does not render the placeholder when not viewing the end', () => {
    const { discussion, stream } = seed(4);
    jest.spyOn(discussion, 'canReply').mockReturnValue(true);
    stream.reset(0, 2);

    expect(render(discussion, stream)).not.toHaveElement('.ReplyPlaceholder');
  });

  describe('reply navigation', () => {
    let container: HTMLElement;

    function mount(discussion: Discussion, stream: PostStreamState): any {
      let vnode: any;
      container = document.createElement('div');
      document.body.appendChild(container);
      m.mount(container, { view: () => (vnode = m(PostStream, { discussion, stream })) });

      return vnode.state;
    }

    function replyItem(): Element {
      return container.querySelector('.ReplyPlaceholder')!.closest('.PostStream-item')!;
    }

    function addBelowReply() {
      extend(PostStream.prototype, 'endItems', function (items: ItemList<any>) {
        items.add('below', m('div', { className: 'PostStream-item TestBelow', key: 'below', role: 'article' }), -200);
      });
    }

    function flashedByReplyJump(instance: any): Element | undefined {
      instance.scrollToItem = jest.fn();
      instance.flashItem = jest.fn();
      instance.scrollToIndex(instance.stream.count(), false, true);

      return (instance.flashItem as jest.Mock<any>).mock.calls[0]?.[0]?.[0];
    }

    function scrolledToBySettling(instance: any): Element | undefined {
      const seen: Element[] = [];
      const offset = jest.spyOn($.fn, 'offset').mockImplementation(function (this: JQuery) {
        seen.push(this[0]);
        return { top: 0, left: 0 };
      } as any);

      instance.getMarginTop = () => 0;
      instance.settlingTarget = { index: instance.stream.count(), reply: true };
      instance.reScrollToTarget();
      offset.mockRestore();

      return seen[0];
    }

    afterEach(() => {
      m.mount(container, null);
      container.remove();
    });

    it('jumps to the placeholder', () => {
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);

      expect(flashedByReplyJump(instance)).toBe(replyItem());
    });

    it('jumps to the placeholder when an extension renders an item below it', () => {
      addBelowReply();
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);

      expect(container.querySelector('.PostStream > :last-child')!.classList.contains('TestBelow')).toBe(true);
      expect(flashedByReplyJump(instance)).toBe(replyItem());
    });

    it('keeps the placeholder in place while settling when an extension renders an item below it', () => {
      addBelowReply();
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);

      expect(scrolledToBySettling(instance)).toBe(replyItem());
    });

    it('keeps the placeholder aligned with the composer while settling when an extension renders an item below it', () => {
      addBelowReply();
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);
      const reply = replyItem();

      jest.spyOn($.fn, 'offset').mockImplementation(function (this: JQuery) {
        return { top: this[0] === reply ? 1000 : 0, left: 0 };
      } as any);
      jest.spyOn($.fn, 'height').mockImplementation(function (this: JQuery) {
        return this[0] === (window as any) ? 800 : 100;
      } as any);
      jest.spyOn((app as any).composer, 'computedHeight').mockReturnValue(300);
      const scrolledTo: number[] = [];
      jest.spyOn($.fn, 'scrollTop').mockImplementation(function (this: JQuery, value?: number) {
        if (value !== undefined) scrolledTo.push(value);
        return this;
      } as any);

      instance.getMarginTop = () => 50;
      instance.settlingTarget = { index: stream.count(), reply: true };
      instance.reScrollToTarget();

      // The placeholder's bottom (1000 + 100) meets the composer's top (800 - 300).
      expect(scrolledTo).toEqual([600]);
    });

    it('finishes a reply jump when there is no item to scroll to', async () => {
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);

      instance.$replyItem = () => $();
      instance.calculatePosition = jest.fn();
      instance.beginSettling = jest.fn();
      instance.loadPostsIfNeeded = jest.fn();
      instance.updateScrubber = jest.fn();
      stream.loadPromise = Promise.resolve();
      stream.paused = true;

      await instance.scrollToItem($(), false, true, true);

      expect(stream.paused).toBe(false);
    });

    it('falls back to the last post rather than an item rendered after it', () => {
      extend(PostStream.prototype, 'endItems', function (items: ItemList<any>) {
        items.remove('reply');
      });
      const originalAfterPost = PostStream.prototype.afterPostItems;
      extend(PostStream.prototype, 'afterPostItems', function (items: ItemList<any>) {
        items.add('advert', m('div', { className: 'TestAfterPost' }));
      });

      try {
        const { discussion, stream } = seed(2);
        jest.spyOn(discussion, 'canReply').mockReturnValue(true);
        const instance = mount(discussion, stream);

        expect(container.querySelector('.PostStream > :last-child')!.classList.contains('PostStream-afterPost')).toBe(true);
        expect(flashedByReplyJump(instance)).toBe(container.querySelector('.PostStream-item[data-index="1"]'));
      } finally {
        PostStream.prototype.afterPostItems = originalAfterPost;
      }
    });

    it('jumps to the last post when an extension removes the placeholder', () => {
      extend(PostStream.prototype, 'endItems', function (items: ItemList<any>) {
        items.remove('reply');
        items.add('below', m('div', { className: 'TestNotAnItem', key: 'below' }));
      });
      const { discussion, stream } = seed(2);
      jest.spyOn(discussion, 'canReply').mockReturnValue(true);
      const instance = mount(discussion, stream);

      expect(flashedByReplyJump(instance)).toBe(container.querySelector('.PostStream-item[data-index="1"]'));
    });
  });

  function render(discussion: Discussion, stream: PostStreamState) {
    return mq(PostStream, { discussion, stream });
  }
});

describe('PostStream look-ahead loading', () => {
  const viewport = 800;
  let nextId = 2000;

  function setUp(postHeight: number, [start, end]: [number, number]) {
    const base = nextId;
    nextId += 121;
    const postIds = Array.from({ length: 120 }, (_, i) => String(base + i));
    app.store.pushPayload({
      data: {
        id: String(base),
        type: 'discussions',
        attributes: { title: 'Discussion title' },
        relationships: { posts: { data: postIds.map((id) => ({ id, type: 'posts' })) } },
      },
    });

    const stream = new PostStreamState(app.store.getById<Discussion>('discussions', String(base))!, []);
    stream.reset(start, end);
    stream.loadPage = jest.fn();
    stream.loadNext = jest.fn(() => stream._loadNext());
    stream.loadPrevious = jest.fn(() => stream._loadPrevious());

    const instance = new (PostStream as any)();
    instance.stream = stream;
    instance.getMarginTop = () => 0;
    instance.element = document.createElement('div');
    for (let index = 0; index < 120; index++) {
      instance.element.insertAdjacentHTML('beforeend', `<div class="PostStream-item" data-index="${index}"></div>`);
    }

    jest.spyOn($.fn, 'height').mockImplementation(function (this: JQuery) {
      return this[0] === (window as any) ? viewport : 0;
    } as any);
    jest.spyOn($.fn, 'offset').mockImplementation(function (this: JQuery) {
      return { top: Number((this[0] as HTMLElement).dataset.index) * postHeight, left: 0 };
    } as any);
    jest.spyOn($.fn, 'outerHeight').mockReturnValue(postHeight as any);

    return { instance, stream };
  }

  function check({ postHeight, window, top, direction }: { postHeight: number; window: [number, number]; top: number; direction: number }) {
    const { instance, stream } = setUp(postHeight, window);
    instance.loadPostsIfNeeded(top, direction);

    return stream;
  }

  afterEach(() => jest.restoreAllMocks());

  it('requests the next page while the end of the stream is still several viewports below', () => {
    // The end of the stream (60,000) is 2,000 px below the viewport.
    const stream = check({ postHeight: 1000, window: [0, 60], top: 57200, direction: 1 });

    expect(stream.loadNext).toHaveBeenCalled();
  });

  it('requests the previous page while the start of the stream is still several viewports above', () => {
    const stream = check({ postHeight: 1000, window: [40, 100], top: 42000, direction: -1 });

    expect(stream.loadPrevious).toHaveBeenCalled();
  });

  it('only looks ahead in the direction the reader scrolls', () => {
    expect(check({ postHeight: 1000, window: [0, 60], top: 57200, direction: -1 }).loadNext).not.toHaveBeenCalled();
    expect(check({ postHeight: 1000, window: [40, 100], top: 42000, direction: 1 }).loadPrevious).not.toHaveBeenCalled();
    expect(check({ postHeight: 1000, window: [0, 60], top: 57200, direction: 0 }).loadNext).not.toHaveBeenCalled();
  });

  it('does not request pages that are further away than the look-ahead', () => {
    const stream = check({ postHeight: 1000, window: [40, 100], top: 70000, direction: 1 });

    expect(stream.loadNext).not.toHaveBeenCalled();
    expect(stream.loadPrevious).not.toHaveBeenCalled();
  });

  it('does not load the next page early when that would unload posts on screen', () => {
    // Loading the next page unloads posts 0-40; the viewport shows 32-39.
    const stream = check({ postHeight: 100, window: [0, 60], top: 3200, direction: 1 });

    expect(stream.loadNext).not.toHaveBeenCalled();
  });

  it('does not load the previous page early when that would unload posts on screen', () => {
    // Loading the previous page unloads posts 60-99; the viewport shows 54-61.
    const stream = check({ postHeight: 100, window: [40, 100], top: 5400, direction: -1 });

    expect(stream.loadPrevious).not.toHaveBeenCalled();
  });

  it('loads the next page early when what it unloads is well off screen', () => {
    // Loading the next page unloads posts 41-80, which end 1,000 px above the viewport.
    const stream = check({ postHeight: 200, window: [41, 100], top: 17200, direction: 1 });

    expect(stream.loadNext).toHaveBeenCalled();
  });

  it('still loads the next page once its end is about to come into view', () => {
    const stream = check({ postHeight: 100, window: [0, 60], top: 5000, direction: 0 });

    expect(stream.loadNext).toHaveBeenCalled();
  });

  it('does not load back the page it has just unloaded while the reader scrolls up', () => {
    const { instance, stream } = setUp(100, [40, 100]);

    // Within 300 px of the start: the previous page loads and 60-99 unload.
    instance.loadPostsIfNeeded(4100, -1);
    expect(stream.visibleEnd).toBe(60);

    instance.loadPostsIfNeeded(4050, -1);

    expect(stream.loadNext).not.toHaveBeenCalled();
  });
});

describe('PostStream placeholder height', () => {
  let nextId = 900;
  let container: HTMLElement;
  const heights: Record<string, number> = {};
  const originalOffsetHeight = Object.getOwnPropertyDescriptor(HTMLElement.prototype, 'offsetHeight')!;

  function seed(postCount: number, loadedOffsets: number[]) {
    const base = nextId;
    nextId += postCount + 1;

    const discussionId = String(base);
    const postIds = Array.from({ length: postCount }, (_, i) => String(base + 1 + i));

    app.store.pushPayload({
      data: {
        id: discussionId,
        type: 'discussions',
        attributes: { title: 'Discussion title' },
        relationships: { posts: { data: postIds.map((id) => ({ id, type: 'posts' })) } },
      },
    });

    const load = (offsets: number[]) =>
      app.store.pushPayload({
        data: offsets.map((offset) => ({
          id: postIds[offset],
          type: 'posts',
          attributes: { number: offset + 1, contentType: 'comment', canEdit: true, createdAt: new Date(), contentHtml: `<p>Post</p>` },
          relationships: { discussion: { data: { id: discussionId, type: 'discussions' } } },
        })),
      });

    load(loadedOffsets);

    const discussion = app.store.getById<Discussion>('discussions', discussionId)!;
    const stream = new PostStreamState(discussion, []);
    stream.reset(0, loadedOffsets.length);

    return { discussion, stream, load };
  }

  function mount(discussion: Discussion, stream: PostStreamState) {
    container = document.createElement('div');
    document.body.appendChild(container);
    m.mount(container, { view: () => m(PostStream, { discussion, stream }) });
  }

  function minHeightOf(index: number): string {
    return (container.querySelector(`.PostStream-item[data-index="${index}"] > .Post`) as HTMLElement).style.minHeight;
  }

  beforeEach(() => {
    if (!app.session) app.boot();

    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', {
      configurable: true,
      get(this: HTMLElement) {
        return this.classList.contains('Post') ? heights[this.parentElement?.dataset.number ?? ''] ?? 0 : 0;
      },
    });
  });

  afterEach(() => {
    Object.defineProperty(HTMLElement.prototype, 'offsetHeight', originalOffsetHeight);
    m.mount(container, null);
    container.remove();
    for (const key of Object.keys(heights)) delete heights[key];
  });

  it('sizes placeholders like the posts already loaded', () => {
    const { discussion, stream } = seed(4, [0, 1]);
    heights['1'] = 300;
    heights['2'] = 500;
    mount(discussion, stream);

    stream.visibleEnd = 4;
    m.redraw.sync();

    expect(minHeightOf(2)).toBe('400px');
    expect(minHeightOf(3)).toBe('400px');
  });

  it('keeps a placeholder at the height it was given while the loaded posts change', () => {
    const { discussion, stream } = seed(4, [0, 1]);
    heights['1'] = 300;
    heights['2'] = 500;
    mount(discussion, stream);
    stream.visibleEnd = 4;
    m.redraw.sync();

    heights['1'] = 900;
    m.redraw.sync();

    expect(minHeightOf(2)).toBe('400px');
  });

  it('drops the height once the post has loaded', () => {
    const { discussion, stream, load } = seed(4, [0, 1]);
    heights['1'] = 300;
    heights['2'] = 500;
    mount(discussion, stream);
    stream.visibleEnd = 4;
    m.redraw.sync();

    load([2]);
    m.redraw.sync();

    expect(minHeightOf(2)).toBe('');
    expect(minHeightOf(3)).toBe('400px');
  });

  it('sizes placeholders by the typical post rather than one very tall one', () => {
    const { discussion, stream } = seed(5, [0, 1, 2]);
    heights['1'] = 100;
    heights['2'] = 120;
    heights['3'] = 15000;
    mount(discussion, stream);

    stream.visibleEnd = 5;
    m.redraw.sync();

    expect(minHeightOf(3)).toBe('120px');
  });

  it('leaves placeholders at their own height when no post has loaded to measure', () => {
    const { discussion, stream } = seed(2, []);
    stream.reset(0, 2);
    mount(discussion, stream);
    m.redraw.sync();

    expect(minHeightOf(0)).toBe('');
  });
});
