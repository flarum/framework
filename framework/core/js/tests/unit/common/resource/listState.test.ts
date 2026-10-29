import { jest } from '@jest/globals';
import { app } from '../../../../src/admin';
import Model from '../../../../src/common/Model';
import type ItemList from '../../../../src/common/utils/ItemList';
import ResourceDefinition from '../../../../src/common/resource/ResourceDefinition';
import ResourceListState from '../../../../src/common/resource/ResourceListState';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
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
