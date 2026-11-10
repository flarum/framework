import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import UserListPage from '../../../../src/admin/components/UserListPage';
import { app } from '../../../../src/admin';
import mq from 'mithril-query';
import m from 'mithril';

beforeAll(() => bootstrapAdmin());

describe('UserListPage', () => {
  const originalRouteParam = m.route.param;
  const originalFind = app.store.find;

  let routeParams: Record<string, string | undefined>;
  let findCalls: any[][];

  beforeAll(() => {
    app.boot();
  });

  beforeEach(() => {
    routeParams = {};
    findCalls = [];

    // @ts-ignore
    m.route.param = (key?: string) => (key ? routeParams[key] : routeParams);

    // @ts-ignore
    app.store.find = (...args: any[]) => {
      findCalls.push(args);
      return Promise.resolve([]);
    };

    window.history.replaceState(null, '', '/admin#/users');
  });

  afterEach(() => {
    m.route.param = originalRouteParam;
    app.store.find = originalFind;
  });

  const flushAsync = () => new Promise((resolve) => setTimeout(resolve, 0));

  const userGambits = () => app.search.gambits.for('users').filter((gambit) => gambit.enabled());

  const searchInput = (header: any) => header.find('input[type=search]')[0] as HTMLInputElement;

  /**
   * Load the page, then render its header: the test DOM (domino) cannot
   * parse the grid's `--columns` custom property, so the loaded grid itself
   * can't be rendered here.
   */
  const loadedHeader = async () => {
    let page!: UserListPage;
    mq(UserListPage, { oninit: (vnode: any) => (page = vnode.state) });
    await flushAsync();

    const header = mq({ view: () => m('.UserListPage-header', page.headerItems().toArray()) });

    // The autocomplete dropdown measures the input, which domino can't do.
    (searchInput(header) as any).getBoundingClientRect = () => ({ bottom: 0 });

    return header;
  };

  test('it renders', () => {
    const page = mq(UserListPage);

    expect(page).toHaveElement('.UserListPage');
  });

  test('it searches for the query in the URL, so other pages can link to a filtered list', async () => {
    routeParams = { q: 'group:Mods' };

    const header = await loadedHeader();

    expect(findCalls[0][1].filter).toEqual({ q: 'group:Mods' });
    expect(searchInput(header).value).toBe('group:Mods');
  });

  test('it keeps the query in the URL', async () => {
    const header = await loadedHeader();

    header.setValue('input[type=search]', 'group:Mods');
    await new Promise((resolve) => setTimeout(resolve, 300));

    expect(new URLSearchParams(window.location.hash.split('?')[1]).get('q')).toBe('group:Mods');
  });

  test('it shows every user filter as a chip', async () => {
    const header = await loadedHeader();

    expect(userGambits().length).toBeGreaterThan(0);
    expect(header.find('.UserListPage-filters .UserListPage-filter')).toHaveLength(userGambits().length);
  });

  test('a filter chip starts that filter in the search', async () => {
    const header = await loadedHeader();

    header.click('.UserListPage-filters .UserListPage-filter');
    header.redraw();

    expect(searchInput(header).value).toBe(userGambits()[0].suggestion().key + ':');
  });
});
