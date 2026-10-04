import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import ModalManagerState from 'flarum/common/states/ModalManagerState';
import AddColumnModal from '../../../../src/forum/components/AddColumnModal';
import DeckState from '../../../../src/forum/states/DeckState';
import deckColumnTypes, { registerDefaultColumnTypes } from '../../../../src/forum/columns/deckColumnTypes';
import { boot, loadDeckTranslations, setLayout } from '../helpers';

beforeAll(() => {
  boot();
  loadDeckTranslations();
  if (!deckColumnTypes.has('filter')) registerDefaultColumnTypes();
});

let root: HTMLElement;

beforeEach(() => {
  root = document.createElement('div');
  document.body.appendChild(root);
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
  jest.restoreAllMocks();
});

/** Lets the picker's chunks load (in tests, plain imports). */
const settle = () => new Promise((resolve) => setTimeout(resolve, 0));

describe('adding a custom filter column', () => {
  it('opens the filter window straight away, and adds a column with the filters chosen', async () => {
    setLayout([]);
    const deck = new DeckState();
    const add = jest.spyOn(deck, 'addColumn').mockImplementation(() => {});
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});

    m.mount(root, {
      view: () =>
        m(AddColumnModal, {
          state: new ModalManagerState(),
          animateShow: () => {},
          animateHide: () => {},
          deck,
        }),
    });

    const filterType = [...root.querySelectorAll<HTMLButtonElement>('.AddColumnModal-type')].find((button) =>
      button.textContent?.includes('Custom filter')
    )!;
    filterType.click();
    await settle();

    // Stacked over this modal, as the forum's search modal with filters.
    expect(show).toHaveBeenCalledTimes(1);
    const [, attrs, stacked] = show.mock.calls[0] as any[];
    expect(stacked).toBe(true);
    expect(attrs.accept('hello world')).toBe(false);
    expect(attrs.accept('is:unread')).toBe(true);

    attrs.onapply('is:unread');
    m.redraw.sync();
    expect(root.querySelector('.AddColumnModal-pickValue')?.textContent).toBe('is:unread');

    root.querySelector<HTMLFormElement>('form')!.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));

    expect(add).toHaveBeenCalledWith(expect.objectContaining({ type: 'filter', params: { query: 'is:unread' } }));
  });
});
