import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import type ItemList from '../../../../src/common/utils/ItemList';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceListState from '../../../../src/common/resource/ResourceListState';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import ViewPreferences from '../../../../src/common/resource/ViewPreferences';
import type { FieldDescriptor, Filter, FilterDescriptor, FilterResolver } from '../../../../src/common/resource/types';
import { bootAdmin, descriptor, fd, schemaDocument } from './fixtures';

class Thing extends Model {}

const things = descriptor(
  [
    fd('name', 'string', { sortable: true }),
    fd('status', 'string', { constraints: { enum: ['draft', 'live'] } }),
    fd('active', 'boolean'),
    fd('category', 'toOne', { relationship: { types: ['categories'] } }),
  ],
  {
    sorts: ['name', 'createdAt'],
    filters: [
      { key: 'status', kind: 'enum', enum: ['draft', 'live'] },
      { key: 'active', kind: 'boolean' },
      { key: 'group', kind: 'relationship', related: 'groups' },
      { key: 'brand', kind: 'string' },
    ],
  }
);

function results(meta: Record<string, unknown> = {}, links: Record<string, unknown> = {}) {
  return Object.assign([] as Model[], { payload: { data: [], meta, links } });
}

function setUrl(query: string) {
  window.history.replaceState(null, '', `/admin#/things${query ? '?' + query : ''}`);
}

function urlQuery(): URLSearchParams {
  return new URLSearchParams(window.location.hash.split('?', 2)[1] ?? '');
}

describe('ResourceListState', () => {
  let find: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
    app.store.models.things = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
    // Load the descriptor through the real store before list requests are stubbed.
    await new ResourceDefinition('things').load();
    find = jest.fn(() => Promise.resolve(results()));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    setUrl('');
    ViewPreferences.clear();
    window.localStorage.clear();
  });

  afterEach(() => jest.restoreAllMocks());

  async function loaded(definition = new ResourceDefinition<Thing>('things')) {
    const state = new ResourceListState(definition);
    await state.load();

    return state;
  }

  test('an unpaginated type is requested whole, without page parameters, and ?page clamps to 1', async () => {
    jest.restoreAllMocks();
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument({ ...things, pagination: undefined }))) as any;
    await new ResourceDefinition('things').load();
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    setUrl('sort=name&page=3');

    const state = await loaded();

    expect(find).toHaveBeenLastCalledWith('things', { sort: 'name' });
    expect(state.currentPage()).toBe(1);
    expect(urlQuery().has('page')).toBe(false);

    await state.goto(2);

    expect(find).toHaveBeenLastCalledWith('things', { sort: 'name' });
    expect(state.currentPage()).toBe(1);
    expect(state.hasNext()).toBe(false);
  });

  test('reads sort, search, filters and page from the URL', async () => {
    setUrl('sort=-name&q=knife&filter%5Bstatus%5D=live&filter%5Bsecret%5D=x&page=2');

    await loaded();

    expect(find).toHaveBeenLastCalledWith('things', {
      sort: '-name',
      filter: { q: 'knife', status: 'live' },
      page: { offset: 20, limit: 20 },
    });
  });

  test('setSort updates the URL and the request params', async () => {
    const state = await loaded();

    await state.setSort('-name');

    expect(urlQuery().get('sort')).toBe('-name');
    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ sort: '-name' }));
    expect(state.getSort()).toBe('-name');
  });

  test('an unknown sort is ignored, from code and from the URL', async () => {
    setUrl('sort=secret');
    const state = await loaded();

    expect(find).toHaveBeenLastCalledWith('things', expect.not.objectContaining({ sort: 'secret' }));

    await state.setSort('-secret');

    expect(state.getSort()).toBe('');
    expect(urlQuery().get('sort')).toBeNull();
  });

  test('empty filters and an empty search are omitted', async () => {
    const state = await loaded();

    await state.setFilter('status', 'live');
    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ filter: { status: 'live' } }));
    expect(urlQuery().get('filter[status]')).toBe('live');

    await state.setFilter('status', '');
    await state.setQuery('');

    expect((find.mock.calls[find.mock.calls.length - 1] as any)[1].filter).toBeUndefined();
    expect(urlQuery().get('filter[status]')).toBeNull();
  });

  test("isNegatable() honours the definition's Filter.negatable over the descriptor", async () => {
    class Custom extends ResourceDefinition<Thing> {
      filters() {
        const items = super.filters();

        items.setContent('active', { ...items.get('active'), negatable: false });
        items.setContent('brand', { ...items.get('brand'), negatable: true });

        return items;
      }
    }

    const state = await loaded(new Custom('things'));

    expect(state.isNegatable('active')).toBe(false);
    expect(state.isNegatable('brand')).toBe(true);
    expect(state.filterParam('brand', '0')).toEqual(['-brand', '1']);
    expect(state.filterParam('active', '0')).toEqual(['active', '0']);
  });

  test('a filter added to the definition (not described) is restored from the URL', async () => {
    class Extended extends ResourceDefinition<Thing> {
      filters() {
        return super.filters().add('extra', { label: 'Extra', input: { type: 'text' } });
      }
    }

    setUrl('filter%5Bextra%5D=x');
    const state = await loaded(new Extended('things'));

    expect(state.getFilter('extra')).toBe('x');
    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ filter: { extra: 'x' } }));
  });

  test('isNegatable() builds the definition filters once per load', async () => {
    const definition = new ResourceDefinition<Thing>('things');
    const filters = jest.spyOn(definition, 'filters');
    const state = await loaded(definition);

    filters.mockClear();
    state.isNegatable('active');
    state.filterParam('active', '0');
    state.filterParam('status', 'live');

    expect(filters).not.toHaveBeenCalled();
  });

  test('boolean "No" is sent and written as the negated key; the URL form reads back', async () => {
    const state = await loaded();

    expect(state.isNegatable('active')).toBe(true);
    expect(state.isNegatable('status')).toBe(false);

    await state.setFilter('active', '0');

    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ filter: { '-active': '1' } }));
    expect(urlQuery().get('filter[-active]')).toBe('1');
    expect(urlQuery().get('filter[active]')).toBeNull();
    expect(state.getFilter('active')).toBe('0');

    await state.setFilter('active', '1');

    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ filter: { active: '1' } }));
    expect(urlQuery().get('filter[-active]')).toBeNull();

    setUrl('filter%5B-active%5D=1&filter%5B-status%5D=1');
    const reloaded = await loaded();

    expect(reloaded.getFilter('active')).toBe('0');
    expect(reloaded.getFilter('status')).toBe('');
    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ filter: { '-active': '1' } }));
  });

  test('clearFilters keeps the search and resets to page 1', async () => {
    setUrl('q=knife&filter%5Bstatus%5D=live&page=2');
    const state = await loaded();

    await state.clearFilters();

    expect(find).toHaveBeenLastCalledWith('things', { filter: { q: 'knife' }, page: { offset: 0, limit: 20 } });
    expect(urlQuery().get('filter[status]')).toBeNull();
  });

  test('a search sets filter[q] and resets to page 1', async () => {
    setUrl('page=3');
    const state = await loaded();

    await state.setQuery('knife');

    expect(find).toHaveBeenLastCalledWith('things', { filter: { q: 'knife' }, page: { offset: 0, limit: 20 } });
    expect(urlQuery().get('q')).toBe('knife');
    expect(urlQuery().get('page')).toBeNull();
  });

  test('goto writes the page to the URL', async () => {
    const state = await loaded();

    await state.goto(4);

    expect(urlQuery().get('page')).toBe('4');
    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ page: { offset: 60, limit: 20 } }));
  });

  test('a slower earlier goto cannot overwrite a newer one', async () => {
    const state = await loaded();
    const page = (n: number, total: number) =>
      Object.assign([new Thing()] as Model[], { payload: { data: [], meta: { page: { total } }, links: {} }, n });
    const deferred: ((value: unknown) => void)[] = [];

    find.mockImplementation(() => new Promise((resolve) => deferred.push(resolve)));

    const first = state.goto(2);
    const second = state.goto(3);

    deferred[1](page(3, 300));
    await second;
    deferred[0](page(2, 200));
    await first;

    expect(state.currentPage()).toBe(3);
    expect((state.getItems() as any).length).toBe(1);
    expect((state.getPages()[0].items as any).n).toBe(3);
    expect(state.totalItems).toBe(300);
    expect(state.isLoading()).toBe(false);
  });

  test('gotoAfterRemoval goes back a page only when the removed rows emptied it', async () => {
    const two = Object.assign([new Thing(), new Thing()] as Model[], { payload: { data: [], meta: {}, links: {} } });
    find.mockImplementation(() => Promise.resolve(two));
    const state = await loaded();

    await state.goto(3);
    await state.gotoAfterRemoval();
    expect(state.currentPage()).toBe(3);

    await state.gotoAfterRemoval(2);
    expect(state.currentPage()).toBe(2);
  });

  test('without syncUrl, the URL is neither read nor written', async () => {
    setUrl('sort=-name');
    const state = new ResourceListState(new ResourceDefinition<Thing>('things'), { syncUrl: false });
    await state.load();
    await state.setSort('name');

    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ sort: 'name' }));
    expect(urlQuery().get('sort')).toBe('-name');
  });

  test('merges definition.query() under the list params', async () => {
    class WithQuery extends ResourceDefinition<Thing> {
      query() {
        return { include: 'category', filter: { archived: '0' } };
      }
    }

    const state = await loaded(new WithQuery('things'));
    await state.setFilter('status', 'draft');

    expect(find).toHaveBeenLastCalledWith('things', {
      include: 'category',
      filter: { archived: '0', status: 'draft' },
      page: { offset: 0, limit: 20 },
    });
  });

  test('exposes the total when the API sends one', async () => {
    find.mockImplementation(() => Promise.resolve(results({ page: { total: 45 } })));
    const state = await loaded();

    expect(state.hasTotal()).toBe(true);
    expect(state.totalItems).toBe(45);
    expect(state.pageCount()).toBe(3);
  });

  test('an out-of-range page goes to the last page when the total is known', async () => {
    setUrl('page=99');
    find.mockImplementation((_type: string, params: any) =>
      Promise.resolve(params.page.offset >= 45 ? results({ page: { total: 45 } }) : Object.assign([new Thing()], results({ page: { total: 45 } })))
    );

    const state = await loaded();

    expect(find).toHaveBeenLastCalledWith('things', expect.objectContaining({ page: { offset: 40, limit: 20 } }));
    expect(state.currentPage()).toBe(3);
    expect(state.getItems()).toHaveLength(1);
    expect(urlQuery().get('page')).toBe('3');
  });

  test('an empty page above 1 stays put when there is no total', async () => {
    setUrl('page=99');

    const state = await loaded();

    expect(find).toHaveBeenCalledTimes(1);
    expect(state.currentPage()).toBe(99);
  });

  test('a total of 0 replaces the previous total', async () => {
    find.mockImplementation(() => Promise.resolve(results({ page: { total: 45 } })));
    const state = await loaded();

    find.mockImplementation(() => Promise.resolve(results({ page: { total: 0 } })));
    await state.setQuery('nothing');

    expect(state.totalItems).toBe(0);
    expect(state.pageCount()).toBe(1);
  });

  test('a response without a total clears the previous total', async () => {
    find.mockImplementation(() => Promise.resolve(results({ page: { total: 45 } })));
    const state = await loaded();

    find.mockImplementation(() => Promise.resolve(results()));
    await state.setQuery('x');

    expect(state.hasTotal()).toBe(false);
  });

  test('a search, sort or filter change keeps the current rows until the new page arrives', async () => {
    const old = new Thing();
    find.mockImplementation(() => Promise.resolve(Object.assign([old], results())));
    const state = await loaded();

    let resolve!: (value: unknown) => void;
    const fresh = new Thing();
    find.mockImplementation(() => new Promise((r) => (resolve = r)));

    for (const change of [() => state.setQuery('knife'), () => state.setSort('name'), () => state.setFilter('status', 'live')]) {
      const pending = change();

      expect(state.getItems()).toEqual([old]);
      expect(state.isLoading()).toBe(true);
      expect(state.isInitialLoading()).toBe(false);

      resolve(Object.assign([old], results()));
      await pending;
    }

    find.mockImplementation(() => Promise.resolve(Object.assign([fresh], results())));
    await state.setQuery('fork');

    expect(state.getItems()).toEqual([fresh]);
    expect(state.isLoading()).toBe(false);
  });

  test('the first load is the initial loading state', async () => {
    let resolve!: (value: unknown) => void;
    find.mockImplementation(() => new Promise((r) => (resolve = r)));
    const state = new ResourceListState(new ResourceDefinition<Thing>('things'));
    const pending = state.load();
    await new Promise((r) => setTimeout(r, 0));

    expect(state.isInitialLoading()).toBe(true);
    expect(state.getItems()).toEqual([]);

    resolve(results());
    await pending;

    expect(state.isInitialLoading()).toBe(false);
  });

  test('without a total it pages by links only', async () => {
    find.mockImplementation(() => Promise.resolve(results({}, { next: 'https://example.test/api/things?page[offset]=20' })));
    const state = await loaded();

    expect(state.hasTotal()).toBe(false);
    expect(state.pageCount()).toBeNull();
    expect(state.hasNext()).toBe(true);
    expect(state.hasPrev()).toBe(false);
  });
  test('a crafted URL adds no request parameters, unknown filters or foreign sorts, and pollutes no prototype', async () => {
    setUrl(
      [
        'sort=-password',
        'include=groups,tokens',
        'fields[things]=password',
        'filter[include]=groups',
        'filter[email]=admin@x',
        'filter[__proto__][polluted]=1',
        'filter[constructor]=1',
        'filter[-__proto__]=1',
        'filter[-constructor]=1',
        'filter[toString]=1',
        'filter[status]=draft',
        'page=-9',
        'q=hello',
      ].join('&')
    );

    await loaded();

    const params = (find.mock.calls.at(-1) as any)[1];
    expect(params.sort).toBeUndefined();
    expect(params.include).toBeUndefined();
    expect(params.fields).toBeUndefined();
    expect(Object.keys(params.filter).sort()).toEqual(['q', 'status']);
    expect(params.page.offset).toBe(0);
    expect(({} as any).polluted).toBeUndefined();
  });

  test.each([
    ['{'],
    ['null'],
    ['[]'],
    ['"str"'],
    ['42'],
    ['{"sort":{"x":1},"filter":[1,2]}'],
    ['{"sort":"-password","filter":{"__proto__":{"polluted":"1"},"constructor":"x","include":"groups","status":"draft,evil"}}'],
  ])('a malformed saved view %s is ignored without throwing', async (raw) => {
    window.localStorage.setItem('flarum.resourceTable.things.view', raw);

    await loaded();

    const params = (find.mock.calls.at(-1) as any)[1];
    expect(params.sort).toBeUndefined();
    expect(params.include).toBeUndefined();
    Object.keys(params.filter ?? {}).forEach((key) => expect(['status']).toContain(key));
    if (params.filter?.status) expect(params.filter.status).toBe('draft');
    expect(({} as any).polluted).toBeUndefined();
  });
});

describe('ResourceDefinition.filters', () => {
  beforeAll(() => bootAdmin());

  beforeEach(() => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(things))) as any;
  });

  test('derives each filter input from the described filter', async () => {
    const filters = (await new ResourceDefinition('things').load()).filters();
    const input = (key: string) => filters.get(key).input as any;

    expect(filters.toArray().length).toBe(4);
    expect(input('status')).toEqual({
      type: 'enum',
      choices: [
        ['draft', 'draft'],
        ['live', 'live'],
      ],
    });
    expect(input('active')).toEqual({ type: 'tri-state' });
    expect(input('group')).toMatchObject({ type: 'relation', related: 'groups' });
    expect(input('brand')).toEqual({ type: 'text' });
  });

  test('labels come from the same-named field, else the humanized key', async () => {
    const filters = (await new ResourceDefinition('things').load()).filters();

    expect(filters.get('status').label).toBe('Status');
    expect(filters.get('group').label).toBe('Group');
  });

  test('a higher-priority filter resolver wins; null falls through', async () => {
    class BrandDefinition extends ResourceDefinition {
      filterResolvers(): ItemList<FilterResolver> {
        return super.filterResolvers().add('brand', (filter) => (filter.key === 'brand' ? { type: 'search', debounce: false } : null), 100);
      }
    }

    const filters = (await new BrandDefinition('things').load()).filters();

    expect(filters.get('brand').input).toEqual({ type: 'search', debounce: false });
    expect(filters.get('active').input).toEqual({ type: 'tri-state' });
  });

  test('filterInput() can decide a filter outright and defer the rest to the resolvers', async () => {
    class BrandDefinition extends ResourceDefinition {
      filterInput(filter: FilterDescriptor, d: FieldDescriptor | null): Filter['input'] {
        return filter.key === 'brand' ? { type: 'search', debounce: false } : super.filterInput(filter, d);
      }
    }

    const filters = (await new BrandDefinition('things').load()).filters();

    expect(filters.get('brand').input).toEqual({ type: 'search', debounce: false });
    expect(filters.get('active').input).toEqual({ type: 'tri-state' });
  });
});

describe('ResourceListState initial view', () => {
  const VIEW = 'flarum.resourceTable.views.view';
  const views = descriptor([fd('name', 'string', { sortable: true }), fd('active', 'boolean')], {
    sorts: ['name'],
    filters: [
      { key: 'status', kind: 'enum', enum: ['draft', 'live'], default: 'live' },
      { key: 'active', kind: 'boolean' },
      { key: 'scope', kind: 'enum', enum: ['mine', 'all'], required: true, default: 'mine' },
    ],
  });
  let find: jest.Mock<any>;

  beforeAll(() => {
    bootAdmin();
    app.store.models.views = Thing;
  });

  beforeEach(async () => {
    clearDescriptorCache();
    app.request = jest.fn(() => Promise.resolve(schemaDocument(views, 'views'))) as any;
    await new ResourceDefinition('views').load();
    find = jest.fn(() => Promise.resolve(results()));
    jest.spyOn(app.store, 'find').mockImplementation(find as any);
    setUrl('');
    ViewPreferences.clear();
    window.localStorage.clear();
  });

  afterEach(() => jest.restoreAllMocks());

  async function loaded(options = {}) {
    const state = new ResourceListState(new ResourceDefinition<Thing>('views'), options);
    await state.load();

    return state;
  }

  const sentFilter = () => (find.mock.calls.at(-1) as any)[1].filter;

  test('without URL params or a saved view, the filter defaults apply', async () => {
    await loaded();

    expect(sentFilter()).toEqual({ status: 'live', scope: 'mine' });
  });

  test('a saved view is used; unknown filters and sorts are dropped; a filter it never named takes its default', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ sort: '-bogus', filter: { active: '0', gone: 'x' } }));

    const state = await loaded();

    expect(sentFilter()).toEqual({ '-active': '1', scope: 'mine', status: 'live' });
    expect(state.getFilter('status')).toBe('live');
    expect((find.mock.calls.at(-1) as any)[1].sort).toBeUndefined();
  });

  test('a saved enum value the descriptor no longer offers is dropped, so the default applies', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ filter: { status: 'archived', scope: 'nowhere' } }));

    const state = await loaded();

    expect(state.savedView()).toEqual({ sort: undefined, filter: {} });
    expect(sentFilter()).toEqual({ status: 'live', scope: 'mine' });
  });

  test('a saved view with a cleared defaulted filter keeps it cleared', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ filter: { status: '' } }));

    await loaded();

    expect(sentFilter()).toEqual({ scope: 'mine' });
  });

  test('a cleared defaulted filter is written to the URL empty and stays cleared on reload', async () => {
    const state = await loaded();

    await state.setFilter('status', '');
    expect(urlQuery().get('filter[status]')).toBe('');

    ViewPreferences.clear();
    window.localStorage.clear();
    await loaded();

    expect(sentFilter()).toEqual({ scope: 'mine' });
  });

  test('a URL naming some filters leaves the others to their defaults unless the saved view names them', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ filter: { active: '1' } }));
    setUrl('filter[active]=0');

    await loaded();

    expect(sentFilter()).toEqual({ '-active': '1', scope: 'mine', status: 'live' });

    window.localStorage.setItem(VIEW, JSON.stringify({ filter: { status: 'draft' } }));
    ViewPreferences.clear();
    setUrl('filter[active]=0');
    await loaded();

    expect(sentFilter()).toEqual({ '-active': '1', scope: 'mine' });
  });

  test('URL params win over the saved view, which loading a link does not overwrite', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ sort: 'name', filter: { status: 'draft' } }));
    setUrl('filter[active]=1&q=x');

    const state = await loaded();

    expect(sentFilter()).toEqual({ active: '1', scope: 'mine', q: 'x' });
    expect(state.params.sort).toBeUndefined();
    expect(JSON.parse(window.localStorage.getItem(VIEW)!)).toEqual({ sort: 'name', filter: { status: 'draft' } });
  });

  test('a URL with only search or page params still opens the saved view', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ sort: 'name', filter: { status: 'draft' } }));
    setUrl('q=x&page=2');

    const state = await loaded();

    expect(state.params).toEqual({ sort: 'name', q: 'x', filter: { status: 'draft', scope: 'mine' } });
  });

  test('changes save the filters and sort, not the search; an emptied view stays empty', async () => {
    const state = await loaded();

    await state.setSort('-name');
    await state.setQuery('ab');
    await state.setFilter('status', '');

    expect(JSON.parse(window.localStorage.getItem(VIEW)!)).toEqual({ sort: '-name', filter: { scope: 'mine', status: '' } });

    setUrl('');
    await loaded();

    // The user cleared `status`; its default does not come back.
    expect(sentFilter()).toEqual({ scope: 'mine' });
  });

  test('a required filter cannot be cleared, by setFilter or clearFilters', async () => {
    const state = await loaded();

    await state.setFilter('scope', 'all');
    await state.setFilter('scope', '');
    expect(state.getFilter('scope')).toBe('all');

    await state.clearFilters();
    expect(state.params.filter).toEqual({ scope: 'all' });
    expect(state.isRequired('scope')).toBe(true);
    expect(state.isRequired('status')).toBe(false);
  });

  test('throwing storage reads as no saved view and keeps the view in memory', async () => {
    jest.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
      throw new Error('denied');
    });
    jest.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
      throw new Error('denied');
    });

    const state = await loaded();

    expect(sentFilter()).toEqual({ status: 'live', scope: 'mine' });

    await state.setFilter('status', 'draft');
    setUrl('');
    await loaded();

    expect(sentFilter()).toEqual({ status: 'draft', scope: 'mine' });
  });

  test('persist: false neither reads nor writes the saved view', async () => {
    window.localStorage.setItem(VIEW, JSON.stringify({ filter: { status: 'draft' } }));

    const state = await loaded({ persist: false });

    expect(sentFilter()).toEqual({ status: 'live', scope: 'mine' });

    await state.setFilter('active', '1');
    expect(JSON.parse(window.localStorage.getItem(VIEW)!)).toEqual({ filter: { status: 'draft' } });
  });

  test('filter labels and negatable come from the descriptor when it says', async () => {
    jest.restoreAllMocks();
    clearDescriptorCache();
    app.request = jest.fn(() =>
      Promise.resolve(
        schemaDocument(
          descriptor([fd('active', 'boolean'), fd('status', 'string')], {
            filters: [
              { key: 'active', kind: 'boolean', label: 'Is active', negatable: false },
              { key: 'status', kind: 'string' },
              { key: 'lastSeen', kind: 'date', negatable: true },
            ],
          }),
          'views'
        )
      )
    ) as any;

    const filters = (await new ResourceDefinition('views').load()).filters();

    expect(filters.get('active')).toMatchObject({ label: 'Is active', negatable: false });
    expect(filters.get('status')).toMatchObject({ label: 'Status', negatable: false });
    expect(filters.get('lastSeen')).toMatchObject({ label: 'Last seen', negatable: true });
  });
});
