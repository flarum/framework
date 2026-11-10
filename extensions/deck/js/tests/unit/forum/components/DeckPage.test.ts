import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import DeckPage from '../../../../src/forum/components/DeckPage';
import DeckState from '../../../../src/forum/states/DeckState';
import { boot, column, registerTestTypes, setLayout } from '../helpers';

beforeAll(() => {
  boot();
  registerTestTypes();
});

function toolbar(layout: ReturnType<typeof column>[] | null) {
  setLayout(layout);

  const page = new DeckPage() as DeckPage & { deck: DeckState };
  page.deck = new DeckState();

  return page;
}

describe('the toolbar', () => {
  it('offers adding a column, and keeps resetting in its menu', () => {
    const page = toolbar([column('a')]);

    expect(Object.keys(page.actionItems().toObject())).toEqual(['addColumn', 'fullscreen', 'options']);
    expect(page.optionItems().has('reset')).toBe(true);
  });

  it('only lets a customised deck be reset', () => {
    expect((toolbar(null).optionItems().get('reset') as any).attrs.disabled).toBe(true);
    expect(
      (
        toolbar([column('a')])
          .optionItems()
          .get('reset') as any
      ).attrs.disabled
    ).toBe(false);
  });
});

describe('full screen', () => {
  let appEl: HTMLElement;

  beforeEach(() => {
    // Core's bootstrap may already have the app element; the class goes on whichever is first.
    appEl = document.getElementById('app') ?? document.body.appendChild(Object.assign(document.createElement('div'), { id: 'app' }));
    appEl.classList.remove('App--deckFullscreen');
    localStorage.removeItem('flarum-deck.fullscreen');
  });

  afterEach(() => {
    appEl.classList.remove('App--deckFullscreen');
    localStorage.removeItem('flarum-deck.fullscreen');
  });

  it('is offered from the toolbar, which gains starting a discussion while in it', () => {
    const page = toolbar([column('a')]);

    expect(page.actionItems().has('fullscreen')).toBe(true);
    expect(page.actionItems().has('newDiscussion')).toBe(false);

    page.setFullscreen(true);

    expect(page.actionItems().has('newDiscussion')).toBe(true);
    expect(appEl.classList.contains('App--deckFullscreen')).toBe(true);
    expect(localStorage.getItem('flarum-deck.fullscreen')).toBe('true');

    page.setFullscreen(false);

    expect(page.actionItems().has('newDiscussion')).toBe(false);
    expect(appEl.classList.contains('App--deckFullscreen')).toBe(false);
    expect(localStorage.getItem('flarum-deck.fullscreen')).toBeNull();
  });

  it('comes back the way it was left', () => {
    localStorage.setItem('flarum-deck.fullscreen', 'true');

    expect(toolbar([column('a')]).isFullscreen()).toBe(true);
  });

  it('leaves on Escape, unless a modal has the key', () => {
    const page = toolbar([column('a')]);
    page.setFullscreen(true);

    const isModalOpen = jest.spyOn(app.modal, 'isModalOpen').mockReturnValue(true);
    page.onKeyDown(new KeyboardEvent('keydown', { key: 'Escape' }));
    expect(page.isFullscreen()).toBe(true);

    isModalOpen.mockReturnValue(false);
    page.onKeyDown(new KeyboardEvent('keydown', { key: 'Escape' }));
    expect(page.isFullscreen()).toBe(false);
  });
});
