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

describe('a value the column type turns away', () => {
  beforeAll(() => {
    if (deckColumnTypes.has('test-code')) return;

    deckColumnTypes.add('test-code', {
      icon: 'fas fa-vial',
      label: () => 'Code',
      title: () => 'Code',
      isAvailable: () => true,
      fields: () => [{ key: 'code', label: 'Code', parse: (value: string) => (value === 'ok' ? value : null), invalidText: (value: string) => `${value} won't do` }],
      createSource: () => ({}) as any,
    });
  });

  it('shows what the type says about what was typed', () => {
    setLayout([]);

    m.mount(root, {
      view: () => m(AddColumnModal, { state: new ModalManagerState(), animateShow: () => {}, animateHide: () => {}, deck: new DeckState() }),
    });

    [...root.querySelectorAll<HTMLButtonElement>('.AddColumnModal-type')].find((button) => button.textContent?.includes('Code'))!.click();
    m.redraw.sync();

    const input = root.querySelector<HTMLInputElement>('#AddColumnModal-code')!;
    input.value = 'nope';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    root.querySelector<HTMLFormElement>('form')!.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    m.redraw.sync();

    expect(root.querySelector('.AddColumnModal-error')?.textContent).toBe("nope won't do");
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

describe('editing a column', () => {
  function mount(deck: DeckState, id: string) {
    const column = deck.columns().find((column) => column.config.id === id)!.config;

    m.mount(root, {
      view: () => m(AddColumnModal, { state: new ModalManagerState(), animateShow: () => {}, animateHide: () => {}, deck, column }),
    });
  }

  const submit = () => {
    root.querySelector<HTMLFormElement>('form')!.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    m.redraw.sync();
  };

  const rename = (name: string) => {
    const input = root.querySelector<HTMLInputElement>('#AddColumnModal-name')!;
    input.value = name;
    input.dispatchEvent(new Event('input', { bubbles: true }));
  };

  it('starts from the column’s settings and name, without opening the filter window', async () => {
    setLayout([{ id: 'f', type: 'filter', width: 280, row: 0, params: { query: 'tag:support is:unread' }, name: 'Support' }]);
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(new DeckState(), 'f');
    await settle();
    m.redraw.sync();

    expect(show).not.toHaveBeenCalled();
    expect(root.querySelector('.AddColumnModal-type')).toBeNull();
    expect(root.querySelector('.AddColumnModal-pickValue')?.textContent).toBe('tag:support is:unread');
    expect(root.querySelector<HTMLInputElement>('#AddColumnModal-name')!.value).toBe('Support');
    // It can't become another kind of column.
    expect(root.textContent).not.toContain('Back');
  });

  it('saves new filters and a name to the same column', async () => {
    setLayout([{ id: 'f', type: 'filter', width: 280, row: 0, params: { query: 'is:unread' } }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    const add = jest.spyOn(deck, 'addColumn');
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(deck, 'f');

    // Shows the type's own title until it's given a name.
    expect(root.querySelector<HTMLInputElement>('#AddColumnModal-name')!.placeholder).toBe('is:unread');

    root.querySelector<HTMLButtonElement>('.AddColumnModal-pick')!.click();
    await settle();

    const [, attrs] = show.mock.calls[0] as any[];
    expect(attrs.value).toBe('is:unread');
    attrs.onapply('is:unread -author:normal');

    rename('Mine to read');
    submit();

    expect(add).not.toHaveBeenCalled();
    expect(edit).toHaveBeenCalledWith('f', { params: { query: 'is:unread -author:normal' }, name: 'Mine to read' });
  });

  it('only renames a column with no settings', () => {
    setLayout([{ id: 'u', type: 'unread', width: 280, row: 0, params: {} }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    mount(deck, 'u');

    expect(root.querySelectorAll('input').length).toBe(1);

    rename('Catch up');
    submit();

    expect(edit).toHaveBeenCalledWith('u', { params: {}, name: 'Catch up' });
  });

  it('keeps a member the column was already for, unless another is picked', () => {
    app.store.pushPayload({ data: [{ type: 'users', id: '31', attributes: { username: 'user31', displayName: 'User 31' } }] } as any);

    const memberType = Object.keys(deckColumnTypes.toObject()).find(
      (key) => deckColumnTypes.get(key).fields?.()[0]?.search?.source().resource === 'users'
    )!;
    const params = deckColumnTypes.get(memberType).fields!()[0].search!.params(app.store.getById('users', '31')!);

    setLayout([{ id: 'm', type: memberType, width: 280, row: 0, params }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    mount(deck, 'm');

    expect(root.querySelector('.AddColumnModal-pickValue')?.textContent).toContain('User 31');

    submit();

    expect(root.querySelector('.AddColumnModal-error')).toBeNull();
    expect(edit).toHaveBeenCalledWith('m', { params, name: '' });
  });

  it('takes a new pick in one of two search fields', async () => {
    app.store.pushPayload({
      data: ['41', '42', '43'].map((id) => ({ type: 'users', id, attributes: { username: `user${id}`, displayName: `User ${id}` } })),
    } as any);

    if (!deckColumnTypes.has('test-pair')) {
      const field = (key: string) => ({
        key,
        label: key,
        search: { source: () => ({ resource: 'users' }) as any, label: (user: any) => user.displayName(), params: (user: any) => ({ [key]: user.id() }) },
      });

      deckColumnTypes.add('test-pair', {
        icon: 'fas fa-vial',
        label: () => 'Pair',
        title: () => 'Pair',
        isAvailable: () => true,
        fields: () => [field('first'), field('second')],
        createSource: () => ({}) as any,
      } as any);
    }

    setLayout([{ id: 'p', type: 'test-pair', width: 280, row: 0, params: { first: '41', second: '42' } }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(deck, 'p');

    root.querySelector<HTMLButtonElement>('#AddColumnModal-first')!.click();
    await settle();
    (show.mock.calls[0] as any[])[1].onpick(app.store.getById('users', '43'));
    submit();

    expect(edit).toHaveBeenCalledWith('p', { params: { first: '43', second: '42' }, name: '' });
  });

  it('replaces everything an earlier pick saved when its only search field is picked again', async () => {
    app.store.pushPayload({ data: [{ type: 'users', id: '51', attributes: { username: 'user51', displayName: 'User 51' } }] } as any);

    if (!deckColumnTypes.has('test-shape')) {
      deckColumnTypes.add('test-shape', {
        icon: 'fas fa-vial',
        label: () => 'Shape',
        title: () => 'Shape',
        isAvailable: () => true,
        // Some results save more than others.
        fields: () => [{ key: 'who', label: 'Who', search: { source: () => ({ resource: 'users' }) as any, label: () => '', params: (user: any) => ({ who: user.id() }) } }],
        createSource: () => ({}) as any,
      } as any);
    }

    setLayout([{ id: 's', type: 'test-shape', width: 280, row: 0, params: { who: '50', parent: '7' } }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(deck, 's');

    root.querySelector<HTMLButtonElement>('.AddColumnModal-pick')!.click();
    await settle();
    (show.mock.calls[0] as any[])[1].onpick(app.store.getById('users', '51'));
    submit();

    expect(edit).toHaveBeenCalledWith('s', { params: { who: '51' }, name: '' });
  });

  it('starts a select it has no saved value for on its first option, as when adding', () => {
    if (!deckColumnTypes.has('test-select')) {
      deckColumnTypes.add('test-select', {
        icon: 'fas fa-vial',
        label: () => 'Select',
        title: () => 'Select',
        isAvailable: () => true,
        // A field added after columns of this type were saved.
        fields: () => [{ key: 'order', label: 'Order', options: () => ({ newest: 'Newest', oldest: 'Oldest' }) }],
        createSource: () => ({}) as any,
      } as any);
    }

    setLayout([{ id: 'o', type: 'test-select', width: 280, row: 0, params: [] as any }]);
    const deck = new DeckState();
    const edit = jest.spyOn(deck, 'editColumn').mockImplementation(() => {});
    mount(deck, 'o');

    submit();

    expect(root.querySelector('.AddColumnModal-error')).toBeNull();
    expect(edit).toHaveBeenCalledWith('o', { params: { order: 'newest' }, name: '' });
  });

  it('doesn’t count the column being edited as already in the deck', async () => {
    app.store.pushPayload({ data: [{ type: 'users', id: '32', attributes: { username: 'user32', displayName: 'User 32' } }] } as any);

    const memberType = Object.keys(deckColumnTypes.toObject()).find(
      (key) => deckColumnTypes.get(key).fields?.()[0]?.search?.source().resource === 'users'
    )!;
    const params = deckColumnTypes.get(memberType).fields!()[0].search!.params(app.store.getById('users', '32')!);

    setLayout([{ id: 'm', type: memberType, width: 280, row: 0, params }]);
    const show = jest.spyOn(app.modal, 'show').mockImplementation(async () => {});
    mount(new DeckState(), 'm');

    root.querySelector<HTMLButtonElement>('.AddColumnModal-pick')!.click();
    await settle();

    const [, attrs] = show.mock.calls[0] as any[];
    expect(attrs.isAdded(app.store.getById('users', '32'))).toBe(false);
  });
});
