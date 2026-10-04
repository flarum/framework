import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import DeckColumn from '../../../../src/forum/components/DeckColumn';
import DeckState from '../../../../src/forum/states/DeckState';
import { boot, column, loadDeckTranslations, registerTestTypes, setLayout } from '../helpers';

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
