import { jest } from '@jest/globals';
import DeckRow from '../../../../src/forum/components/DeckRow';
import DeckState from '../../../../src/forum/states/DeckState';
import { RecordingSource, boot, column, loadDeckTranslations, registerTestTypes, setLayout } from '../helpers';

beforeAll(() => {
  // Not in jsdom; the row only uses it to show its scroll buttons.
  (globalThis as any).ResizeObserver ??= class {
    observe() {}
    disconnect() {}
  };
  boot();
  loadDeckTranslations();
  registerTestTypes();
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

function mount(deck: DeckState): void {
  m.mount(root, {
    view: () =>
      m(DeckRow, { deck, columns: deck.rows()[0], row: 0, sortable: null, scrollLeft: 0, onscrolled: () => {}, ondrag: () => {} }),
  });
}

const element = (id: string) => root.querySelector(`.DeckColumn[data-column-id="${id}"]`);

describe('a column whose settings change', () => {
  // The new settings come with a new source, which has to load and be watched
  // for coming on screen like any column just added.
  it('is shown afresh, loading what it now shows', () => {
    setLayout([column('a')]);
    const deck = new DeckState();
    const load = jest.spyOn(RecordingSource.prototype, 'load');
    mount(deck);
    const before = element('a');

    deck.editColumn('a', { params: { query: 'is:unread' } });
    m.redraw.sync();

    expect(element('a')).not.toBe(before);
    expect(load).toHaveBeenCalledTimes(2);
  });

  it('stays as it is when only renamed', () => {
    setLayout([column('a')]);
    const deck = new DeckState();
    const load = jest.spyOn(RecordingSource.prototype, 'load');
    mount(deck);
    const before = element('a');

    deck.editColumn('a', { params: {}, name: 'To do' });
    m.redraw.sync();

    expect(element('a')).toBe(before);
    expect(load).toHaveBeenCalledTimes(1);
    expect(element('a')!.querySelector('.DeckColumn-titleText')!.textContent).toBe('To do');
  });
});
