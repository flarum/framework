import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import ModalManagerState from 'flarum/common/states/ModalManagerState';
import SearchState from 'flarum/common/states/SearchState';
import DeckFilterModal from '../../../../src/forum/components/DeckFilterModal';
import { boot, discussionData, loadDeckTranslations } from '../helpers';

beforeAll(() => {
  boot();
  loadDeckTranslations();
  app.store.pushPayload({ data: discussionData('61') } as any);
});

let root: HTMLElement;

/** Stands in for core's Link, which navigates from its own click handler. */
const navigate = jest.fn();

beforeEach(() => {
  navigate.mockReset();
  root = document.createElement('div');
  document.body.appendChild(root);
});

afterEach(() => {
  m.mount(root, null);
  root.remove();
});

/** A preview of discussions matching the query, linking away as core's do. */
class FakeSource {
  resource = 'discussions';

  title() {
    return 'Discussions';
  }
  isCached() {
    return true;
  }
  async search() {}
  view() {
    return [m('li', { 'data-id': '61', 'data-index': 'discussions61' }, m('a', { href: '/d/61', onclick: navigate }, 'Discussion 61'))];
  }
  customGrouping() {
    return false;
  }
  fullPage() {
    return null;
  }
  gotoItem() {
    return '/d/61';
  }
}

/** Filters only, as the custom filter column wants. */
const filtersOnly = (query: string) => !!query && !app.search.gambits.match('discussions', query, () => {});

function open(query: string) {
  const onapply = jest.fn();
  const animateHide = jest.fn();
  const searchState = new SearchState();
  searchState.setValue(query);

  m.mount(root, {
    view: () =>
      m(DeckFilterModal, {
        state: new ModalManagerState(),
        animateShow: () => {},
        animateHide,
        title: 'Filters',
        sources: [new FakeSource()],
        searchState,
        onchange: () => {},
        onpick: () => {},
        accept: filtersOnly,
        invalidText: 'Filters only, please.',
        onapply,
      }),
  });

  return { onapply, animateHide };
}

const useButton = () => [...root.querySelectorAll<HTMLButtonElement>('.DeckFilterModal-footer button')].pop()!;

describe('DeckFilterModal', () => {
  it('uses the filters written', () => {
    const { onapply, animateHide } = open('is:unread');

    useButton().click();

    expect(onapply).toHaveBeenCalledWith('is:unread');
    expect(animateHide).toHaveBeenCalled();
  });

  it('turns plain words away, saying why', () => {
    const { onapply, animateHide } = open('hello world');

    useButton().click();
    m.redraw.sync();

    expect(onapply).not.toHaveBeenCalled();
    expect(animateHide).not.toHaveBeenCalled();
    expect(root.querySelector('.DeckFilterModal-error')?.textContent).toBe('Filters only, please.');
  });

  it('uses the filters on Enter', () => {
    const { onapply } = open('is:unread');

    root.querySelector('.SearchModal-input')!.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, which: 13, bubbles: true }));

    expect(onapply).toHaveBeenCalledWith('is:unread');
  });

  it('only previews matching discussions', () => {
    const { onapply } = open('is:unread');

    root.querySelector<HTMLElement>('.SearchModal-results li[data-id="61"] a')!.click();

    expect(navigate).not.toHaveBeenCalled();
    expect(onapply).not.toHaveBeenCalled();
  });

  it('suggests filters as they are typed', () => {
    open('is');

    expect(root.querySelector('.SearchModal-options')).not.toBeNull();
  });
});
