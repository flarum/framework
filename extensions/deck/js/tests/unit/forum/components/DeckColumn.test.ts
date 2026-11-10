import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import DeckColumn from '../../../../src/forum/components/DeckColumn';
import DeckState from '../../../../src/forum/states/DeckState';
import { boot, column, loadDeckTranslations, registerTestTypes, setLayout, sourceOf } from '../helpers';

beforeAll(() => {
  boot();
  loadDeckTranslations();
  registerTestTypes();
});

let root: HTMLElement;

beforeEach(() => {
  jest.useFakeTimers();
  root = document.createElement('div');
  document.body.appendChild(root);
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
  jest.restoreAllMocks();
  jest.useRealTimers();
});

function deckOf(...ids: Array<[string, number]>): DeckState {
  setLayout(ids.map(([id, row]) => column(id, row)));

  return new DeckState();
}

/** Renders one of the deck's columns, as DeckRow would. */
function render(deck: DeckState, id: string, flat = false): void {
  m.mount(root, {
    view: () =>
      m(DeckColumn, {
        deck,
        column: deck.columns().find((column) => column.config.id === id)!,
        flat,
      }),
  });
}

const has = (item: string) => !!root.querySelector(`.DeckColumn-menu .item-${item}`);
const click = (item: string) => root.querySelector<HTMLButtonElement>(`.DeckColumn-menu .item-${item} button`)!.click();
const order = (deck: DeckState) => deck.columns().map((column) => column.config.id);

describe('moving left and right from the column menu', () => {
  it('offers only the moves there is room for', () => {
    const deck = deckOf(['a', 0], ['b', 0], ['c', 0]);

    render(deck, 'a');
    expect([has('moveLeft'), has('moveRight')]).toEqual([false, true]);

    render(deck, 'b');
    expect([has('moveLeft'), has('moveRight')]).toEqual([true, true]);

    render(deck, 'c');
    expect([has('moveLeft'), has('moveRight')]).toEqual([true, false]);
  });

  it('keeps within the row side by side, but crosses rows on the single strip', () => {
    const deck = deckOf(['a', 0], ['b', 1]);

    render(deck, 'a');
    expect(has('moveRight')).toBe(false);

    render(deck, 'a', true);
    expect(has('moveRight')).toBe(true);
  });

  it('moves the column', () => {
    const deck = deckOf(['a', 0], ['b', 0], ['c', 0]);

    render(deck, 'a');
    click('moveRight');

    expect(order(deck)).toEqual(['b', 'a', 'c']);
  });

  it('says where the column went', () => {
    const deck = deckOf(['a', 0], ['b', 0], ['c', 1]);

    render(deck, 'a');
    click('moveRight');
    expect(deck.announcement).toBe('Moved to position 2 of 2 in the top row.');

    render(deck, 'c');
    click('moveRow');
    expect(deck.announcement).toBe('Moved to position 3 of 3 in the top row.');

    render(deck, 'c', true);
    click('moveLeft');
    expect(deck.announcement).toBe('Moved to position 2 of 3.');
  });

  it('gives focus back to the column’s menu', () => {
    const deck = deckOf(['a', 0], ['b', 0]);

    render(deck, 'a');
    click('moveRight');
    jest.runOnlyPendingTimers();

    expect(document.activeElement).toBe(root.querySelector('.DeckColumn-menu .Dropdown-toggle'));
  });

  // Rows run right to left, so left is later in the order.
  it('follows a right-to-left layout', () => {
    jest.spyOn(window, 'getComputedStyle').mockReturnValue({ direction: 'rtl' } as CSSStyleDeclaration);
    const deck = deckOf(['a', 0], ['b', 0], ['c', 0]);

    render(deck, 'a');
    expect([has('moveLeft'), has('moveRight')]).toEqual([true, false]);

    click('moveLeft');
    expect(order(deck)).toEqual(['b', 'a', 'c']);
  });
});

describe('the column menu', () => {
  /** Lays out what jsdom can't: the menu button's place on screen and the menu's height. */
  function openMenu({ top, phone = false }: { top: number; phone?: boolean }) {
    const deck = deckOf(['a', 0]);
    render(deck, 'a');

    const dropdown = root.querySelector<HTMLElement>('.DeckColumn-menu')!;
    const menu = dropdown.querySelector<HTMLElement>('.Dropdown-menu')!;
    const toggle = dropdown.querySelector<HTMLElement>('.Dropdown-toggle')!;

    // Only the menu's position is faked: core's own handler reads styles too.
    const real = window.getComputedStyle.bind(window);
    jest.spyOn(window, 'getComputedStyle').mockImplementation((element: Element, pseudo?: string | null) => {
      const style = real(element, pseudo);

      if (element !== menu) return style;

      return new Proxy(style, {
        get: (target: any, property) =>
          property === 'position'
            ? phone
              ? 'fixed'
              : 'absolute'
            : typeof target[property] === 'function'
            ? target[property].bind(target)
            : target[property],
      });
    });
    toggle.getBoundingClientRect = () => ({ top, bottom: top + 36, left: 900, right: 936 } as DOMRect);
    Object.defineProperty(menu, 'scrollHeight', { value: 400 });
    Object.defineProperty(window, 'innerHeight', { value: 768, configurable: true });

    $(dropdown).trigger('shown.bs.dropdown');

    return { dropdown, menu };
  }

  it('is pinned below its button, out of reach of the row clipping it', () => {
    const { menu } = openMenu({ top: 200 });

    expect(menu.style.position).toBe('fixed');
    expect(menu.style.top).toBe('240px');
    expect(menu.style.maxHeight).toBe('520px');
  });

  it('opens upwards when there is more room above', () => {
    const { menu } = openMenu({ top: 600 });

    // jsdom drops `top: auto`; browsers keep it.
    expect(menu.style.top).not.toContain('px');
    expect(menu.style.bottom).toBe('172px');
    expect(menu.style.maxHeight).toBe('588px');
  });

  it('leaves phones to core, which shows a sheet along the bottom', () => {
    const { menu } = openMenu({ top: 200, phone: true });

    expect(menu.getAttribute('style')).toBeNull();
  });

  it('is unpinned when it closes', () => {
    const { dropdown, menu } = openMenu({ top: 200 });

    $(dropdown).trigger('hidden.bs.dropdown');

    expect(menu.getAttribute('style')).toBeNull();
  });

  it('closes when the deck scrolls under it, but not when it scrolls itself', () => {
    const { dropdown, menu } = openMenu({ top: 200 });
    const toggle = jest.fn();
    ($.fn as any).dropdown = toggle;
    dropdown.classList.add('open');

    menu.dispatchEvent(new Event('scroll'));
    expect(toggle).not.toHaveBeenCalled();

    root.dispatchEvent(new Event('scroll'));
    expect(toggle).toHaveBeenCalledWith('toggle');
  });
});

// Reading a discussion sends no event, so coming back to the deck is when a
// column notices, e.g. Unread letting go of what was just read.
it('prunes a column when the deck is shown again', () => {
  const deck = deckOf(['a', 0]);
  deck.columns();
  const prune = jest.fn();
  (sourceOf('a') as any).prune = prune;

  render(deck, 'a');

  expect(prune).toHaveBeenCalled();
});

describe('resizing a column', () => {
  const handle = () => root.querySelector<HTMLElement>('.DeckColumn-resize')!;
  const key = (key: string, shiftKey = false) => handle().dispatchEvent(new KeyboardEvent('keydown', { key, shiftKey, bubbles: true }));
  const width = (deck: DeckState) => deck.columns()[0].config.width;

  it('widens and narrows from the keyboard, by more with Shift, and Home resets it', () => {
    const deck = deckOf(['a', 0], ['b', 0]);
    render(deck, 'a');

    key('ArrowRight');
    expect(width(deck)).toBe(300);

    key('ArrowLeft', true);
    expect(width(deck)).toBe(240);

    key('Home');
    expect(width(deck)).toBe(280);
  });

  it('follows a right-to-left layout', () => {
    jest.spyOn(window, 'getComputedStyle').mockReturnValue({ direction: 'rtl' } as CSSStyleDeclaration);
    const deck = deckOf(['a', 0], ['b', 0]);
    render(deck, 'a');

    key('ArrowLeft');

    expect(width(deck)).toBe(300);
  });

  it('resets on double-click', () => {
    const deck = deckOf(['a', 0], ['b', 0]);
    deck.setWidth('a', 500);
    render(deck, 'a');

    handle().dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));

    expect(width(deck)).toBe(280);
  });

  it('follows the pointer, saving once the drag ends', () => {
    const save = setLayout([column('a'), column('b'), column('c')]);
    const deck = new DeckState();
    render(deck, 'a');

    // Three columns of 280 stretched to fill a 1200px row: 400 each.
    const section = root.querySelector<HTMLElement>('.DeckColumn')!;
    section.getBoundingClientRect = () => ({ width: 400 } as DOMRect);
    Object.defineProperty(root, 'clientWidth', { value: 1200, configurable: true });

    const pointer = (type: string, clientX: number) =>
      handle().dispatchEvent(Object.assign(new MouseEvent(type, { bubbles: true, clientX, button: 0 }), { pointerId: 1 }));

    pointer('pointerdown', 1000);
    pointer('pointermove', 1100);
    jest.runOnlyPendingTimers();

    // Shown 500 beside 560 of others in 1200: 500 * 560 / 700.
    expect(width(deck)).toBe(400);
    expect(save).not.toHaveBeenCalled();

    pointer('pointerup', 1100);
    jest.runOnlyPendingTimers();

    expect(save).toHaveBeenCalledTimes(1);
  });

  it('offers to reset a width that has been changed', () => {
    const deck = deckOf(['a', 0], ['b', 0]);
    render(deck, 'a');
    expect(has('resetWidth')).toBe(false);

    deck.setWidth('a', 500);
    m.redraw.sync();
    expect(has('resetWidth')).toBe(true);

    click('resetWidth');
    expect(width(deck)).toBe(280);
  });
});
