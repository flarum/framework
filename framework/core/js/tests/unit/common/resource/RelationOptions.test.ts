import { jest } from '@jest/globals';
import type Model from '../../../../src/common/Model';
import RelationInput from '../../../../src/common/resource/inputs/RelationInput';
import RelationOptions from '../../../../src/common/resource/inputs/RelationOptions';
import { bootAdmin } from './fixtures';

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
});
