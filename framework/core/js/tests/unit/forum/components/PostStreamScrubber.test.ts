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
    description: 'March 2024',
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

/**
 * Each element's tag, classes, own text and inline styles, in document order.
 * Style declarations are sorted, as their order depends on the order they
 * were set in.
 */
function describeDom(element: Element): unknown {
  const style = (element as HTMLElement).style;

  return {
    tag: element.tagName,
    className: element.className,
    text: Array.from(element.childNodes)
      .filter((node) => node.nodeType === Node.TEXT_NODE)
      .map((node) => node.textContent)
      .join(''),
    style: Array.from({ length: style.length }, (_, i) => `${style[i]}: ${style.getPropertyValue(style[i])}`).sort(),
    children: Array.from(element.children).map(describeDom),
  };
}

/**
 * Wait for loads, timers and jQuery animations to finish.
 */
function settle() {
  return new Promise((resolve) => setTimeout(resolve, 20));
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

    // onresize() fits the track's max-height above #app's bottom padding.
    const appElement = document.getElementById('app') ?? document.body.appendChild(Object.assign(document.createElement('div'), { id: 'app' }));
    appElement.style.paddingBottom = '0px';
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

    // A fractional visible count comes from posts partly in view, and leaves
    // rounding error in the share of the posts outside a full-height handle.
    test.each([
      [1, 3],
      [10, 3],
      [-10, 3],
      [1, 1.0324324324324323],
      [10, 1.0324324324324323],
    ])('stays put by %ipx when the handle fills the track, with %d posts visible', (pixels, visible) => {
      class TallHandleScrubber extends PostStreamScrubber {
        static minHandleHeight = 150;
      }

      trackHeight = 100;

      const { scrubber } = mount(TallHandleScrubber, { stream: fakeStream({ visible }) });

      expect(scrubber.percentPerPost().index).toBe(0);

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
    ['scrubberItems', 50, () => m('div.Scrubber-extra', 'Extra')],
    ['scrollbarItems', 90, () => m('div.Scrubber-extra')],
    ['scrollbarItems', 90, () => m('div.Scrubber-extra', { style: { position: 'absolute', top: '0', height: '100%' } })],
    ['infoItems', 50, () => m('span.Scrubber-extra', 'Extra')],
    ['infoItems', 50, () => m('span.Scrubber-extra', [m('i.icon'), ' Extra'])],
  ] as const)('when an extension item in %s at %i appears and disappears', (list, priority, item) => {
    test.each([0, 7])('the scrubber matches a fresh one, with %i unread posts', async (unread) => {
      let showExtra = false;

      class ToggledScrubber extends PostStreamScrubber {}

      extend(ToggledScrubber.prototype, list, function (items: ItemList<Mithril.Children>) {
        if (showExtra) items.add('extra', item(), priority);
      });

      const attrs = () => ({ stream: fakeStream({ discussion: { unreadCount: () => unread } }) });
      const { root, scrubber, redraw } = mount(ToggledScrubber, attrs());
      await settle();

      for (const show of [true, false, true]) {
        showExtra = show;
        redraw();
        await settle();

        const { root: fresh } = mount(ToggledScrubber, attrs());
        await settle();

        expect(describeDom(root)).toEqual(describeDom(fresh));
      }

      $(root.querySelector('.Scrubber-handle')!).trigger($.Event('mousedown', { clientY: 100 }));
      $(document).trigger($.Event('mousemove', { clientY: 130 }));

      expect(scrubber.stream.index).toBeGreaterThan(5);

      $(document).trigger($.Event('mouseup'));
      $(root.querySelector('.Scrubber-scrollbar')!).trigger($.Event('click', { pageY: 150 }));

      expect(scrubber.stream.goToIndex).toHaveBeenCalledTimes(2);
      expect((scrubber.stream.goToIndex as jest.Mock).mock.calls.every(([index]) => Number.isInteger(index))).toBe(true);
    });
  });

  describe('when an extension item in scrollbarItems changes during an animation', () => {
    beforeEach(() => {
      $.fx.off = false;
    });

    afterEach(() => {
      $.fx.off = true;
    });

    test.each([
      ['appears', 'with its own height', false, () => m('div.Scrubber-extra', { style: { position: 'absolute', height: '100%' } })],
      ['appears', 'without styles of its own', false, () => m('div.Scrubber-extra')],
      ['disappears', 'with its own height', true, () => m('div.Scrubber-extra', { style: { position: 'absolute', height: '100%' } })],
    ] as const)('the scrubber matches a fresh one once the item %s %s', async (_change, _styles, startShown, item) => {
      let showExtra: boolean = startShown;

      class ToggledScrubber extends PostStreamScrubber {}

      extend(ToggledScrubber.prototype, 'scrollbarItems', function (items: ItemList<Mithril.Children>) {
        if (showExtra) items.add('extra', item(), 90);
      });

      const attrs = () => {
        const stream = fakeStream({ index: 30 });
        stream.goToFirst = jest.fn(() => {
          stream.index = 1;
          return Promise.resolve();
        });
        return { stream };
      };

      const { root, scrubber, redraw } = mount(ToggledScrubber, attrs());
      await settle();

      // "Original Post" animates the handle to the top of the track.
      scrubber.goToFirst();
      await new Promise((resolve) => setTimeout(resolve, 30));
      expect($(root).find(':animated').length).toBeGreaterThan(0);

      showExtra = !startShown;
      redraw();

      await new Promise((resolve) => setTimeout(resolve, 600));

      const fresh = mount(ToggledScrubber, attrs());
      fresh.scrubber.goToFirst();
      await new Promise((resolve) => setTimeout(resolve, 600));

      expect(describeDom(root)).toEqual(describeDom(fresh.root));
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
