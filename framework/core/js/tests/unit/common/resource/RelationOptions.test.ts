import { jest } from '@jest/globals';
import type Model from '../../../../src/common/Model';
import RelationInput from '../../../../src/common/resource/inputs/RelationInput';
import RelationOptions from '../../../../src/common/resource/inputs/RelationOptions';
import { app } from '../../../../src/admin';
import { clearDescriptorCache } from '../../../../src/common/resource/descriptor';
import { bootAdmin, descriptor } from './fixtures';

type Page = { items: Model[]; hasMore: boolean };

const flush = () => new Promise((resolve) => setTimeout(resolve, 0));
const record = (id: string) => ({ id: () => id }) as unknown as Model;

function deferred() {
  let resolve!: (page: Page) => void;
  let reject!: (error: unknown) => void;
  const promise = new Promise<Page>((res, rej) => ((resolve = res), (reject = rej)));

  return { promise, resolve, reject };
}

describe('RelationOptions', () => {
  beforeAll(() => bootAdmin());

  afterEach(() => {
    jest.useRealTimers();
    jest.restoreAllMocks();
  });

  test('a failed load is not marked loaded; ensureLoaded() and retry() fetch again', async () => {
    jest.spyOn(console, 'error').mockImplementation(() => {});
    const options = new RelationOptions('groups');
    const fetchPage = jest
      .spyOn(options, 'fetchPage')
      .mockImplementationOnce(() => Promise.reject(new Error('500')))
      .mockImplementationOnce(() => Promise.reject(new Error('500')))
      .mockImplementation(() => Promise.resolve({ items: [record('1')], hasMore: false }));

    options.ensureLoaded();
    await flush();

    expect(options.loaded).toBe(false);
    expect(options.failed).toBe(true);
    expect(options.isLoading).toBe(false);

    options.ensureLoaded();
    await flush();
    expect(fetchPage).toHaveBeenCalledTimes(2);
    expect(options.failed).toBe(true);

    await options.retry();

    expect(fetchPage).toHaveBeenCalledTimes(3);
    expect(options.loaded).toBe(true);
    expect(options.failed).toBe(false);
    expect(options.items.map((item) => item.id())).toEqual(['1']);

    options.ensureLoaded();
    expect(fetchPage).toHaveBeenCalledTimes(3);
  });

  test('a response still in flight when search() starts its debounce is dropped', async () => {
    jest.useFakeTimers();
    const options = new RelationOptions('groups');
    const first = deferred();
    const second = deferred();
    jest
      .spyOn(options, 'fetchPage')
      .mockImplementationOnce(() => first.promise)
      .mockImplementationOnce(() => second.promise);

    const initial = options.loadItems();
    options.search('mod');

    // The old response lands during the debounce.
    first.resolve({ items: [record('old')], hasMore: true });
    await initial;

    expect(options.items).toEqual([]);
    expect(options.hasMore).toBe(false);

    jest.advanceTimersByTime(RelationInput.SEARCH_DELAY);
    second.resolve({ items: [record('new')], hasMore: false });
    await Promise.resolve();
    await Promise.resolve();
    await Promise.resolve();

    expect(options.items.map((item) => item.id())).toEqual(['new']);
    expect(options.loaded).toBe(true);
  });

  describe("fetchPage() and the related type's pagination", () => {
    let find: jest.Mock<(...args: any[]) => Promise<any>>;

    const results = (ids: string[], next: boolean) => Object.assign(ids.map(record), { payload: { links: next ? { next: 'x' } : {} } });

    function serve(schema: () => Promise<any>, rows: (params: any) => any) {
      find = jest.fn((type: string, idOrParams: any) => (type === 'resource-schemas' ? schema() : Promise.resolve(rows(idOrParams))));
      jest.spyOn(app.store, 'find').mockImplementation(find as any);
    }

    const listCalls = () => find.mock.calls.filter(([type]) => type !== 'resource-schemas');

    beforeEach(() => clearDescriptorCache());

    test('an unpaginated type is requested whole: no page parameters, every record, no "Load more"', async () => {
      const all = Array.from({ length: 25 }, (_, i) => String(i + 1));
      serve(
        () => Promise.resolve({ payload: { data: { attributes: descriptor([], { pagination: undefined }) } } }),
        () => results(all, false)
      );
      const options = new RelationOptions('groups');

      await options.loadItems();

      expect(listCalls()).toEqual([['groups', {}]]);
      expect(options.items).toHaveLength(25);
      expect(options.hasMore).toBe(false);
    });

    test('an unpaginated type keeps the search filter', async () => {
      serve(
        () => Promise.resolve({ payload: { data: { attributes: descriptor([], { pagination: undefined }) } } }),
        () => results(['1'], false)
      );
      const options = new RelationOptions('groups');
      options.query = 'mod';

      await options.loadItems();

      expect(listCalls()).toEqual([['groups', { filter: { q: 'mod' } }]]);
    });

    test('a paginated type is paged and "Load more" requests the next offset', async () => {
      serve(
        () => Promise.resolve({ payload: { data: { attributes: descriptor([]) } } }),
        (params) => (params.page.offset === 0 ? results(['1', '2'], true) : results(['3'], false))
      );
      const options = new RelationOptions('users', 2);

      await options.loadItems();
      expect(options.hasMore).toBe(true);

      await options.loadMore();

      expect(listCalls()).toEqual([
        ['users', { page: { offset: 0, limit: 2 } }],
        ['users', { page: { offset: 2, limit: 2 } }],
      ]);
      expect(options.items.map((item) => item.id())).toEqual(['1', '2', '3']);
      expect(options.hasMore).toBe(false);
    });

    test('a descriptor that cannot be loaded falls back to paging', async () => {
      serve(
        () => Promise.reject({ status: 403 }),
        () => results(['1'], true)
      );
      const options = new RelationOptions('groups', 20);

      await options.loadItems();

      expect(listCalls()).toEqual([['groups', { page: { offset: 0, limit: 20 } }]]);
      expect(options.failed).toBe(false);
      expect(options.hasMore).toBe(true);
    });

    test('a descriptor that cannot be loaded is asked for once, not on every page', async () => {
      serve(
        () => Promise.reject({ status: 403 }),
        () => results(['1'], true)
      );
      const options = new RelationOptions('groups', 20);

      await options.loadItems();
      await options.loadMore();

      expect(find.mock.calls.filter(([type]) => type === 'resource-schemas')).toHaveLength(1);
      expect(listCalls()).toHaveLength(2);
    });

    test('RelationInput pages through the same request', async () => {
      serve(
        () => Promise.resolve({ payload: { data: { attributes: descriptor([], { pagination: undefined }) } } }),
        () => results(['1', '2'], false)
      );

      const page = await RelationInput.fetchPage('groups', { offset: 0, limit: 20 });

      expect(listCalls()).toEqual([['groups', {}]]);
      expect(page.items).toHaveLength(2);
      expect(page.hasMore).toBe(false);
    });
  });
});
