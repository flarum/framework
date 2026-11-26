import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import type Discussion from 'flarum/common/models/Discussion';
import { jest } from '@jest/globals';
import extend from '../../../src/forum/extend';
import IdeationPage from '../../../src/forum/components/IdeationPage';

beforeAll(() => {
  bootstrapForum();
  app.boot();
  // The attributes this extension adds to discussions.
  extend.forEach((extender) => extender.extend(app, { name: 'flarum-ideation', exports: {} } as any));
});

afterEach(() => {
  jest.restoreAllMocks();
  jest.useRealTimers();
});

/** A request the test answers when it chooses to. */
function deferred() {
  let resolve!: (value: any) => void;
  const promise = new Promise<any>((r) => (resolve = r));

  return { promise, resolve };
}

/** An API answer listing these ideas, each with its vote count. */
const payload = (ideas: Record<string, number>) => ({
  data: Object.entries(ideas).map(([id, votes]) => ({
    type: 'discussions',
    id,
    attributes: { title: 'Idea ' + id, ideationStatus: 'under_review', ideationVotes: votes },
  })),
  links: {},
});

/** The page in list view, with the two requests it will make answered by hand. */
function listPage(q = '') {
  const page = Object.create(IdeationPage.prototype) as IdeationPage;
  Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q, columns: {} });

  const requests = [deferred(), deferred()];
  let call = 0;
  const request = jest.spyOn(app, 'request').mockImplementation((() => requests[call++].promise) as any);

  return { page, requests, request };
}

const tick = () => new Promise((r) => setTimeout(r));
const ids = (page: IdeationPage) => page.columns.list.items.map((d) => d.id());

describe('the roadmap list', () => {
  it('keeps the newest search when an older answer arrives last', async () => {
    const { page, requests } = listPage('dark');

    page.loadColumn('list');
    page.q = 'dark mode';
    page.loadColumn('list');

    // The newer request answers first, then the older one.
    requests[1].resolve(payload({ 101: 1 }));
    await tick();
    requests[0].resolve(payload({ 102: 1 }));
    await tick();

    expect(ids(page)).toEqual(['101']);
    expect(page.columns.list.loading).toBe(false);
    // The older answer never reached the store either.
    expect(app.store.getById('discussions', '102')).toBeUndefined();
  });

  it('stays loading until the newest request answers', async () => {
    const { page, requests } = listPage();

    page.loadColumn('list');
    page.loadColumn('list');

    requests[0].resolve(payload({ 103: 1 }));
    await tick();

    expect(page.columns.list.items).toEqual([]);
    expect(page.columns.list.loading).toBe(true);
  });

  it('never lets an older answer roll back an idea already on screen', async () => {
    const { page, requests } = listPage();

    page.loadColumn('list');
    page.loadColumn('list');

    // The newer answer has the idea at 5 votes; the older one, which
    // arrives last, still has it at 3.
    requests[1].resolve(payload({ 104: 5 }));
    await tick();
    requests[0].resolve(payload({ 104: 3 }));
    await tick();

    const idea = app.store.getById<Discussion>('discussions', '104')!;
    expect(idea.attribute('ideationVotes')).toBe(5);
    expect(page.columns.list.items[0]).toBe(idea);
    expect(ids(page)).toEqual(['104']);
  });
});

describe('the roadmap search', () => {
  it('keeps every character typed and searches once typing stops', () => {
    jest.useFakeTimers();

    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q: '', columns: {} });
    const change = jest.spyOn(page, 'change').mockImplementation(() => {});

    const root = document.createElement('div');
    m.mount(root, { view: () => page.toolbar() });
    const input = root.querySelector('.IdeationPage-search') as HTMLInputElement;

    for (const character of 'dark mode') {
      input.value += character;
      input.dispatchEvent(new Event('input', { bubbles: true }));
      // Mithril redraws after every input event.
      m.redraw.sync();
      jest.advanceTimersByTime(100);
    }

    expect(input.value).toBe('dark mode');
    expect(page.q).toBe('dark mode');
    expect(change).not.toHaveBeenCalled();

    jest.advanceTimersByTime(300);
    expect(change).toHaveBeenCalledTimes(1);
    expect(change).toHaveBeenCalledWith({ q: 'dark mode' });

    m.mount(root, null);
  });
});

describe('the roadmap views', () => {
  it('can drag on the board after switching to it from the list', () => {
    app.forum.pushAttributes({ ideationCanModerate: true });
    jest.spyOn(app, 'request').mockImplementation((() => new Promise(() => {})) as any);

    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q: '', columns: {}, drag: null });
    page.load();

    const root = document.createElement('div');
    m.mount(root, { view: () => page.view() });
    expect(root.querySelector('.IdeationList')).not.toBeNull();
    expect(page.drag).toBeNull();

    page.mode = 'board';
    page.load();
    m.redraw.sync();

    expect(root.querySelector('.IdeationBoard')).not.toBeNull();
    expect(page.drag).not.toBeNull();

    // And back: the board's element goes, and its dragging with it.
    page.mode = 'list';
    m.redraw.sync();
    expect(page.drag).toBeNull();

    m.mount(root, null);
  });

  it('cancels a search still waiting when the page goes', () => {
    jest.useFakeTimers();

    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q: '', columns: {} });
    const change = jest.spyOn(page, 'change').mockImplementation(() => {});

    const root = document.createElement('div');
    m.mount(root, { view: () => page.toolbar() });
    const input = root.querySelector('.IdeationPage-search') as HTMLInputElement;
    input.value = 'dark';
    input.dispatchEvent(new Event('input', { bubbles: true }));
    m.mount(root, null);

    page.onremove({ dom: root } as any);
    jest.advanceTimersByTime(1000);

    expect(change).not.toHaveBeenCalled();
  });
});

describe('the board', () => {
  it('reloads the cards shown in a column sorted by votes after a drop, without totals', async () => {
    const moved = app.store.pushPayload<Discussion>({
      data: { type: 'discussions', id: '201', attributes: { title: 'Idea 201', ideationStatus: 'under_review', ideationVotes: 1 } },
    } as any);
    const others = Array.from({ length: 12 }, (_, i) => ({ id: () => String(300 + i) }) as unknown as Discussion);

    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'board', status: 'all', sort: 'votes', q: '', columns: {} });
    for (const key of ['under_review', 'planned', 'in_progress', 'shipped'])
      page.columns[key] = { items: [], loading: false, more: false, request: 0 };
    page.columns.under_review.items = [moved];
    page.columns.planned.items = [...others];

    jest.spyOn(moved, 'save').mockImplementation(() => Promise.resolve(moved) as any);
    const request = jest.spyOn(app, 'request').mockImplementation((() => new Promise(() => {})) as any);

    page.move('201', 'planned');
    expect(page.columns.planned.items[0]).toBe(moved);
    await tick();

    // All 13 cards shown are fetched again, from the top, in the server's order.
    expect(request).toHaveBeenCalledTimes(1);
    const params = (request.mock.calls[0][0] as any).params;
    expect(params.filter.ideation).toBe('planned');
    expect(params.page).toEqual({ offset: 0, limit: 13, total: 0 });
  });

  it('leaves the Shipped column as it is after a drop, as it is sorted by when ideas moved', async () => {
    const moved = app.store.pushPayload<Discussion>({
      data: { type: 'discussions', id: '202', attributes: { title: 'Idea 202', ideationStatus: 'in_progress', ideationVotes: 1 } },
    } as any);

    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'board', status: 'all', sort: 'votes', q: '', columns: {} });
    for (const key of ['under_review', 'planned', 'in_progress', 'shipped'])
      page.columns[key] = { items: [], loading: false, more: false, request: 0 };
    page.columns.in_progress.items = [moved];

    jest.spyOn(moved, 'save').mockImplementation(() => Promise.resolve(moved) as any);
    const request = jest.spyOn(app, 'request');

    page.move('202', 'shipped');
    await tick();

    expect(page.columns.shipped.items).toEqual([moved]);
    expect(request).not.toHaveBeenCalled();
  });
});
