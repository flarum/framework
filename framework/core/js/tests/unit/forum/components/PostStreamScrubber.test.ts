import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from '../../../../src/forum/app';
import PostStreamScrubber from '../../../../src/forum/components/PostStreamScrubber';
import ItemList from '../../../../src/common/utils/ItemList';
import { extend } from '../../../../src/common/extend';
import m from 'mithril';
import type Mithril from 'mithril';

const TRACK_HEIGHT = 300;

let trackHeight = TRACK_HEIGHT;
const mounted: HTMLElement[] = [];

function fakeStream(overrides: Record<string, unknown> = {}) {
  return {
    index: 5,
    visible: 3,
    description: '',
    paused: false,
    forceUpdateScrubber: false,
    loadPromise: Promise.resolve(),
    count: () => 40,
    disabled: () => false,
    sanitizeIndex: (index: number) => index,
    goToIndex: jest.fn(() => Promise.resolve()),
    goToFirst: jest.fn(() => Promise.resolve()),
    goToLast: jest.fn(() => Promise.resolve()),
    discussion: { unreadCount: () => 0 },
    ...overrides,
  };
}

function mount<T extends PostStreamScrubber>(component: { prototype: T }, attrs: Record<string, unknown> = {}) {
  const root = document.createElement('div');
  document.body.appendChild(root);

  const fullAttrs = { stream: fakeStream(), ...attrs };
  const vnode = m(component as any, fullAttrs);
  m.render(root, vnode);
  mounted.push(root);

  const redraw = () => m.render(root, m(component as any, fullAttrs));

  return { root, scrubber: vnode.state as T, redraw };
}

function childClasses(element: Element | null): string[] {
  return Array.from(element?.children ?? []).map((child) => child.className);
}

describe('PostStreamScrubber', () => {
  const originalRequestAnimationFrame = window.requestAnimationFrame;
  const originalOuterHeight = $.fn.outerHeight;

  beforeAll(() => {
    bootstrapForum();
    app.boot();

    // jsdom has no layout, and the bootstrap's synchronous requestAnimationFrame
    // never lets jQuery's animation queue settle.
    $.fx.off = true;
    window.requestAnimationFrame = ((callback: FrameRequestCallback) => setTimeout(callback, 0)) as typeof window.requestAnimationFrame;
    ($.fn as any).outerHeight = () => trackHeight;
  });

  afterAll(() => {
    $.fx.off = false;
    window.requestAnimationFrame = originalRequestAnimationFrame;
    $.fn.outerHeight = originalOuterHeight;
  });

  afterEach(async () => {
    await new Promise((resolve) => setTimeout(resolve, 0));

    mounted.splice(0).forEach((root) => {
      m.render(root, null);
      root.remove();
    });

    trackHeight = TRACK_HEIGHT;
  });

  test('renders the original structure by default', () => {
    const { root } = mount(PostStreamScrubber, { className: 'App-titleControl' });

    expect(root.firstElementChild!.className).toBe('PostStreamScrubber Dropdown App-titleControl');
    expect(childClasses(root.querySelector('.Scrubber'))).toEqual([
      'Scrubber-first Button Button--link hasIcon',
      'Scrubber-scrollbar',
      'Scrubber-last Button Button--link hasIcon',
    ]);
    expect(childClasses(root.querySelector('.Scrubber-scrollbar'))).toEqual([
      'Scrubber-before',
      'Scrubber-handle',
      'Scrubber-after',
      'Scrubber-unread',
    ]);
    expect(Array.from(root.querySelector('.Scrubber-info')!.children).map((child) => child.tagName + '.' + child.className)).toEqual([
      'STRONG.',
      'SPAN.Scrubber-description',
    ]);
    expect(root.querySelectorAll('.Scrubber-index')).toHaveLength(2);
  });

  test('still renders the overridable labels', () => {
    class LabelledScrubber extends PostStreamScrubber {
      firstPostLabel() {
        return 'Top';
      }

      lastPostLabel() {
        return 'Bottom';
      }

      unreadLabel(count: number) {
        return `${count} new`;
      }
    }

    const { root } = mount(LabelledScrubber);

    expect(root.querySelector('.Scrubber-first')!.textContent).toContain('Top');
    expect(root.querySelector('.Scrubber-last')!.textContent).toContain('Bottom');
    expect(root.querySelector('.Scrubber-unread')!.textContent).toBe('0 new');
  });

  test('lets extensions add to the dropdown and to the handle info', () => {
    class ExtendedScrubber extends PostStreamScrubber {}

    extend(ExtendedScrubber.prototype, 'scrubberItems', function (items: ItemList<Mithril.Children>) {
      items.add('myUnread', m('button.Scrubber-myUnread', 'First unread'), 50);
    });
    extend(ExtendedScrubber.prototype, 'infoItems', function (items: ItemList<Mithril.Children>) {
      items.add('author', m('span.Scrubber-author', 'Toby'), 50);
    });

    const { root } = mount(ExtendedScrubber);

    expect(childClasses(root.querySelector('.Scrubber'))[1]).toBe('Scrubber-myUnread');
    expect(childClasses(root.querySelector('.Scrubber-info'))).toEqual(['', 'Scrubber-author', 'Scrubber-description']);
  });

  test('lets extensions add to the root and to the track', () => {
    class ExtendedScrubber extends PostStreamScrubber {}

    extend(ExtendedScrubber.prototype, 'viewItems', function (items: ItemList<Mithril.Children>) {
      items.add('badge', m('span.Scrubber-badge', 'New'), 50);
    });
    extend(ExtendedScrubber.prototype, 'scrollbarItems', function (items: ItemList<Mithril.Children>) {
      items.add('heatmap', m('div.Scrubber-heatmap'), 10);
    });

    const { root } = mount(ExtendedScrubber);

    expect(childClasses(root.firstElementChild)).toEqual(['Button Dropdown-toggle', 'Scrubber-badge', 'Dropdown-menu dropdown-menu']);
    expect(childClasses(root.querySelector('.Scrubber-scrollbar'))).toEqual([
      'Scrubber-before',
      'Scrubber-handle',
      'Scrubber-after',
      'Scrubber-unread',
      'Scrubber-heatmap',
    ]);
  });

  test('reads the minimum handle height from the class', () => {
    class TallHandleScrubber extends PostStreamScrubber {
      static minHandleHeight = 150;
    }

    const { scrubber } = mount(TallHandleScrubber);

    // 150px of a 300px track, shared by the 3 visible posts.
    expect(scrubber.percentPerPost().visible).toBeCloseTo(50 / 3);
  });

  describe('dragging the handle', () => {
    function drag(scrubber: PostStreamScrubber, pixels: number) {
      scrubber.onmousedown({ clientY: 100 } as any);
      scrubber.onmousemove({ clientY: 100 + pixels } as any);
    }

    test('moves by the posts the dragged distance covers', () => {
      const { scrubber } = mount(PostStreamScrubber);
      const { index: percentPerPost } = scrubber.percentPerPost();

      // 30px of the 300px track is 10% of it.
      drag(scrubber, 30);

      expect(scrubber.stream.index).toBeCloseTo(5 + 10 / percentPerPost);

      scrubber.onmouseup();

      expect(scrubber.stream.goToIndex).toHaveBeenCalledWith(Math.floor(5 + 10 / percentPerPost));
    });

    test.each([1, 10, -10])('stays put by %ipx when the handle fills the track', (pixels) => {
      class TallHandleScrubber extends PostStreamScrubber {
        static minHandleHeight = 150;
      }

      trackHeight = 100;

      const { scrubber } = mount(TallHandleScrubber);

      expect(scrubber.percentPerPost().visible * scrubber.stream.visible).toBe(100);

      drag(scrubber, pixels);

      expect(scrubber.stream.index).toBe(5);

      scrubber.onmouseup();

      expect(scrubber.stream.index).toBe(5);
      expect(scrubber.stream.goToIndex).not.toHaveBeenCalled();
    });

    test('does not navigate when the handle is pressed and released in place', () => {
      const { scrubber } = mount(PostStreamScrubber);

      drag(scrubber, 0);
      scrubber.onmouseup();

      expect(scrubber.stream.index).toBe(5);
      expect(scrubber.stream.goToIndex).not.toHaveBeenCalled();
    });
  });

  describe.each([
    ['scrubberItems', 50],
    ['scrollbarItems', 90],
  ] as const)('when an extension item in %s comes and goes', (list, priority) => {
    test('the scrubber can still be dragged and clicked', async () => {
      let showExtra = true;

      class ToggledScrubber extends PostStreamScrubber {}

      extend(ToggledScrubber.prototype, list, function (items: ItemList<Mithril.Children>) {
        if (showExtra) items.add('extra', m('button.Scrubber-extra', 'Extra'), priority);
      });

      const { root, scrubber, redraw } = mount(ToggledScrubber);
      await scrubber.stream.loadPromise;

      const originalHandle = root.querySelector('.Scrubber-handle');

      showExtra = false;
      redraw();

      const handle = root.querySelector<HTMLElement>('.Scrubber-handle')!;

      // Mithril rebuilt the handle, which is what puts its handlers and sizing at risk.
      expect(handle).not.toBe(originalHandle);
      expect(parseFloat(handle.style.height)).toBeCloseTo(16.6667);
      expect(Array.from(root.querySelectorAll('.Scrubber-index')).map((el) => el.textContent)).toEqual(['5', '5']);

      $(handle).trigger($.Event('mousedown', { clientY: 100 }));
      $(document).trigger($.Event('mousemove', { clientY: 130 }));

      expect(scrubber.stream.index).toBeGreaterThan(5);

      $(document).trigger($.Event('mouseup'));
      $(root.querySelector('.Scrubber-scrollbar')!).trigger($.Event('click', { pageY: 150 }));

      expect(scrubber.stream.goToIndex).toHaveBeenCalledTimes(2);
      expect((scrubber.stream.goToIndex as jest.Mock).mock.calls.every(([index]) => Number.isInteger(index))).toBe(true);
    });
  });

  test('tears down the close watcher when the dropdown hides', () => {
    const destroy = jest.fn();
    (window as any).CloseWatcher = class {
      onclose: (() => void) | null = null;
      destroy = destroy;
    };

    try {
      const { root, scrubber } = mount(PostStreamScrubber);
      const $scrubber = $(root.firstElementChild!);

      $scrubber.trigger('shown.bs.dropdown');
      expect(scrubber.closeWatcher).toBeDefined();

      $scrubber.trigger('hidden.bs.dropdown');

      expect(destroy).toHaveBeenCalledTimes(1);
      expect(scrubber.closeWatcher).toBeUndefined();
    } finally {
      delete (window as any).CloseWatcher;
    }
  });
});
