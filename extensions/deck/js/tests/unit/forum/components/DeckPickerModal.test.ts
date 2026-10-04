import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import ModalManagerState from 'flarum/common/states/ModalManagerState';
import SearchState from 'flarum/common/states/SearchState';
import type User from 'flarum/common/models/User';
import DeckPickerModal from '../../../../src/forum/components/DeckPickerModal';
import { boot } from '../helpers';

beforeAll(() => {
  boot();

  app.store.pushPayload({
    data: ['21', '22'].map((id) => ({ type: 'users', id, attributes: { username: `user${id}`, displayName: `User ${id}` } })),
  } as any);
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
  jest.restoreAllMocks();
});

/** Results as core's sources render them: an item with a data-id, linking away. */
class FakeSource {
  resource = 'users';
  fullPageQuery: string | null = null;

  title() {
    return 'Users';
  }
  isCached() {
    return true;
  }
  async search() {}
  view(query: string) {
    return ['21', '22']
      .filter((id) => !query || `user${id}`.includes(query))
      .map((id) => m('li', { 'data-id': id, 'data-index': 'users' + id }, m('a', { href: `/u/user${id}`, onclick: navigate }, `User ${id}`)));
  }
  customGrouping() {
    return false;
  }
  fullPage(query: string) {
    return m('li', m('a', { href: '/search' }, `Search all for ${query}`));
  }
  gotoItem(id: string) {
    return `/u/user${id}`;
  }
}

function open(query: string, attrs: Record<string, unknown> = {}) {
  const onpick = jest.fn();
  const animateHide = jest.fn();
  const searchState = new SearchState();
  searchState.setValue(query);

  m.mount(root, {
    view: () =>
      m(DeckPickerModal, {
        state: new ModalManagerState(),
        animateShow: () => {},
        animateHide,
        title: 'Choose a member',
        sources: [new FakeSource()],
        searchState,
        onchange: () => {},
        onpick,
        ...attrs,
      }),
  });

  return { onpick, animateHide };
}

const results = () => [...root.querySelectorAll('.SearchModal-results li[data-id]')].map((li) => li.getAttribute('data-id'));

describe('DeckPickerModal', () => {
  it('picks the result clicked instead of following its link', () => {
    const { onpick, animateHide } = open('user');

    root.querySelector<HTMLElement>('.SearchModal-results li[data-id="22"] a')!.click();

    expect(onpick).toHaveBeenCalledWith(app.store.getById<User>('users', '22'));
    expect(navigate).not.toHaveBeenCalled();
    expect(animateHide).toHaveBeenCalled();
  });

  it('picks the highlighted result on Enter', () => {
    const { onpick } = open('user');
    const input = root.querySelector<HTMLInputElement>('.SearchModal-input')!;

    input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', keyCode: 13, which: 13, bubbles: true }));

    expect(onpick).toHaveBeenCalledWith(app.store.getById<User>('users', '21'));
  });

  it('never offers to leave for the full search page', () => {
    open('user');

    expect(root.querySelector('.SearchModal-fullPage')).toBeNull();
    expect(root.textContent).not.toContain('Search all for');
  });

  it('shows no tabs for a single source', () => {
    open('user');

    expect(root.querySelectorAll('.Tabs-nav > *')).toHaveLength(0);
  });

  it('lists everything before anything is typed only when browsing', () => {
    open('');
    expect(results()).toEqual([]);

    m.mount(root, null);

    open('', { browse: true });
    expect(results()).toEqual(['21', '22']);
  });

  it('uses its own title', () => {
    open('user');

    expect(root.querySelector('.Modal-header h3')?.textContent).toBe('Choose a member');
  });
});
