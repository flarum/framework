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

  const vnode = m(component as any, { stream: fakeStream(), ...attrs });
  m.render(root, vnode);
  mounted.push(root);

  return { root, scrubber: vnode.state as T };
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

  test('keeps dragging forwards when the minimum handle height exceeds the track', () => {
    class TallHandleScrubber extends PostStreamScrubber {
      static minHandleHeight = 150;
    }

    trackHeight = 100;

    const { scrubber } = mount(TallHandleScrubber);
    const start = scrubber.stream.index;

    expect(scrubber.percentPerPost().index).toBeGreaterThanOrEqual(0);
    expect(scrubber.percentPerPost().visible * scrubber.stream.visible).toBeLessThanOrEqual(100);

    scrubber.onmousedown({ clientY: 100 } as any);
    scrubber.onmousemove({ clientY: 110 } as any);
    scrubber.onmouseup();

    expect(scrubber.stream.index).toBeGreaterThanOrEqual(start);
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
