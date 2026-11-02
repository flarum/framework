import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import IdeationPage from '../../../src/forum/components/IdeationPage';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

afterEach(() => jest.restoreAllMocks());

/** A request the test answers when it chooses to. */
function deferred() {
  let resolve!: (value: any) => void;
  const promise = new Promise<any>((r) => (resolve = r));

  return { promise, resolve };
}

const results = (...ids: string[]) => Object.assign(ids, { payload: { links: {} } });

describe('the roadmap list', () => {
  it('keeps the newest search when an older answer arrives last', async () => {
    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q: 'dark', columns: {} });

    const requests = [deferred(), deferred()];
    let call = 0;
    jest.spyOn(app.store, 'find').mockImplementation((() => requests[call++].promise) as any);

    page.loadColumn('list');
    page.q = 'dark mode';
    page.loadColumn('list');

    // The newer request answers first, then the older one.
    requests[1].resolve(results('new'));
    await new Promise((r) => setTimeout(r));
    requests[0].resolve(results('old'));
    await new Promise((r) => setTimeout(r));

    expect(Array.from(page.columns.list.items)).toEqual(['new']);
    expect(page.columns.list.loading).toBe(false);
  });

  it('stays loading until the newest request answers', async () => {
    const page = Object.create(IdeationPage.prototype) as IdeationPage;
    Object.assign(page, { mode: 'list', status: 'all', sort: 'votes', q: '', columns: {} });

    const requests = [deferred(), deferred()];
    let call = 0;
    jest.spyOn(app.store, 'find').mockImplementation((() => requests[call++].promise) as any);

    page.loadColumn('list');
    page.loadColumn('list');

    requests[0].resolve(results('old'));
    await new Promise((r) => setTimeout(r));

    expect(page.columns.list.items).toEqual([]);
    expect(page.columns.list.loading).toBe(true);
  });
});
