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

describe('what is already in the deck', () => {
  function mount(deck: DeckState) {
    m.mount(root, {
      view: () =>
        m(AddColumnModal, {
          state: new ModalManagerState(),
          animateShow: () => {},
          animateHide: () => {},
          deck,
        }),
    });
  }

  const typeButton = (key: string) => root.querySelector<HTMLButtonElement>(`.AddColumnModal-type[data-type="${key}"]`)!;

  it('marks a column type already in the deck, and offers it again only if it takes settings', () => {
    setLayout([
      { id: 'u', type: 'unread', width: 280, row: 0, params: {} },
      { id: 'f1', type: 'filter', width: 280, row: 0, params: { query: 'is:unread' } },
      { id: 'f2', type: 'filter', width: 280, row: 0, params: { query: 'is:following' } },
    ]);
    mount(new DeckState());

    // Unread takes no settings, so a second one could only be the same column.
    expect(typeButton('unread').disabled).toBe(true);
    expect(typeButton('unread').textContent).toContain('In your deck');

    // Two filter columns, and a third could still be different.
    expect(typeButton('filter').disabled).toBe(false);
    expect(typeButton('filter').textContent).toContain('In your deck ×2');

    expect(typeButton('all').disabled).toBe(false);
    expect(typeButton('all').textContent).not.toContain('In your deck');
  });

  it('tells the picker which results are already columns', async () => {
    app.store.pushPayload({
      data: ['21', '22'].map((id) => ({ type: 'users', id, attributes: { username: `user${id}`, displayName: `User ${id}` } })),
    } as any);

    // A type whose first field is a member picker.
    const memberType = Object.keys(deckColumnTypes.toObject()).find(
      (key) => deckColumnTypes.get(key).fields?.()[0]?.search?.source().resource === 'users'
    )!;
    const search = deckColumnTypes.get(memberType).fields!()[0].search!;

    setLayout([{ id: 'm', type: memberType, width: 280, row: 0, params: search.params(app.store.getById('users', '21')!) }]);
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(new DeckState());

    typeButton(memberType).click();
    await settle();

    const [, attrs] = show.mock.calls[0] as any[];

    expect(attrs.isAdded(app.store.getById('users', '21'))).toBe(true);
    expect(attrs.isAdded(app.store.getById('users', '22'))).toBe(false);
  });
});
