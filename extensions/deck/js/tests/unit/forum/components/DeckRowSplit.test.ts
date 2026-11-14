import { jest } from '@jest/globals';
import DeckRowSplit from '../../../../src/forum/components/DeckRowSplit';
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
  jest.useRealTimers();
});

function mount() {
  const save = setLayout([column('a'), column('b', 1)]);
  const deck = new DeckState();

  m.mount(root, { view: () => m(DeckRowSplit, { deck }) });

  return { deck, save, divider: root.querySelector<HTMLElement>('.DeckRowSplit')! };
}

describe('DeckRowSplit', () => {
  it('describes itself to assistive technology', () => {
    const { divider } = mount();

    expect(divider.getAttribute('role')).toBe('separator');
    expect(divider.getAttribute('aria-orientation')).toBe('horizontal');
    expect(divider.getAttribute('aria-valuenow')).toBe('50');
    expect(divider.getAttribute('aria-label')).toBe('Resize rows');
  });

  it('moves from the keyboard, and Home evens it', () => {
    const { deck, divider } = mount();
    const key = (key: string) => divider.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));

    key('ArrowDown');
    expect(deck.rowSplit()).toBe(0.55);

    key('ArrowUp');
    key('ArrowUp');
    expect(deck.rowSplit()).toBe(0.45);

    key('Home');
    expect(deck.rowSplit()).toBe(0.5);
  });

  it('follows the pointer, saving once the drag ends', () => {
    const { deck, save, divider } = mount();
    root.getBoundingClientRect = () => ({ top: 100, height: 600 } as DOMRect);

    const pointer = (type: string, clientY: number) =>
      divider.dispatchEvent(Object.assign(new MouseEvent(type, { bubbles: true, clientY, button: 0 }), { pointerId: 1 }));

    pointer('pointerdown', 400);
    pointer('pointermove', 280);
    jest.runOnlyPendingTimers();

    expect(deck.rowSplit()).toBe(0.3);
    expect(save).not.toHaveBeenCalled();

    pointer('pointerup', 280);
    jest.runOnlyPendingTimers();

    expect(save).toHaveBeenCalledWith({ deckRowSplit: 0.3 });
  });

  it('evens on double-click', () => {
    const { deck, divider } = mount();
    deck.setRowSplit(0.7);

    divider.dispatchEvent(new MouseEvent('dblclick', { bubbles: true }));

    expect(deck.rowSplit()).toBe(0.5);
  });
});
