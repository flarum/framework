import PaginatedListState, { PaginatedListParams } from '../../../../src/common/states/PaginatedListState';
import Model from '../../../../src/common/Model';

interface TestParams extends PaginatedListParams {
  sort?: string;
  filter?: Record<string, string>;
}

class TestState extends PaginatedListState<Model, TestParams> {
  get type() {
    return 'test';
  }

  requestParams() {
    return {};
  }

  // Expose for spying
  public refreshCount = 0;

  public refresh(page = 1): Promise<void> {
    this.refreshCount++;
    // Simulate a loaded page with items so isEmpty() returns false after first refresh
    this.pages = [{ number: page, items: [{}] as any }];
    this.initialLoading = false;
    return Promise.resolve();
  }
}

/**
 * A state whose page loads resolve when we say so, so that the list can be
 * inspected while a request is still outstanding.
 */
class DeferredState extends PaginatedListState<Model, TestParams> {
  get type() {
    return 'test';
  }

  requestParams() {
    return {};
  }

  public loadPageCalls: number[] = [];
  private resolvers: ((results: any) => void)[] = [];
  private rejecters: ((error: any) => void)[] = [];

  protected loadPage(page = 1): Promise<any> {
    this.loadPageCalls.push(page);

    return new Promise((resolve, reject) => {
      this.resolvers.push(resolve);
      this.rejecters.push(reject);
    });
  }

  /** Fail the oldest outstanding load. */
  public reject(): Promise<void> {
    this.resolvers.shift();
    this.rejecters.shift()!(new Error('network'));

    return Promise.resolve().then(() => undefined);
  }

  /** Settle an outstanding load (the oldest, unless `index` says otherwise) with `count` items. */
  public settle(count: number, label = 'x', index = 0): Promise<void> {
    const resolve = this.resolvers.splice(index, 1)[0];
    this.rejecters.splice(index, 1);
    const items = Array.from({ length: count }, (_, i) => ({ id: `${label}${i}` }));

    resolve(Object.assign(items, { payload: { links: {}, meta: {} } }));

    // Let the .then() chain inside goto()/revalidate() run.
    return Promise.resolve().then(() => undefined);
  }

  public seed(count: number, label = 'seed'): void {
    const items = Array.from({ length: count }, (_, i) => ({ id: `${label}${i}` }));
    this.pages = [{ number: 1, items: items as any, hasNext: false, hasPrev: false }];
  }

  public itemIds(): string[] {
    return this.getPages().flatMap((page) => (page.items as any[]).map((item) => item.id));
  }
}

describe('PaginatedListState', () => {
  describe('paramsChanged', () => {
    test('does not reload when called again with semantically identical primitive params', async () => {
      const state = new TestState({ sort: 'latest' });

      await state.refreshParams({ sort: 'latest' }, 1);
      expect(state.refreshCount).toBe(1);

      await state.refreshParams({ sort: 'latest' }, 1);
      expect(state.refreshCount).toBe(1);
    });

    test('reloads when primitive param value changes', async () => {
      const state = new TestState({ sort: 'latest' });

      await state.refreshParams({ sort: 'latest' }, 1);
      expect(state.refreshCount).toBe(1);

      await state.refreshParams({ sort: 'oldest' }, 1);
      expect(state.refreshCount).toBe(2);
    });

    test('does not reload when called again with semantically identical object params', async () => {
      // This is the bug: filter is a new {} on every call (from stickyParams), so
      // paramsChanged() sees {} !== {} and triggers a reload every time.
      const state = new TestState({ filter: {} });

      await state.refreshParams({ filter: {} }, 1);
      expect(state.refreshCount).toBe(1);

      // Second call with a new {} object — same value, different reference
      await state.refreshParams({ filter: {} }, 1);
      expect(state.refreshCount).toBe(1); // fails before fix: refreshCount is 2
    });

    test('does not reload when called again with semantically identical non-empty filter', async () => {
      const state = new TestState({ filter: { tag: 'foo' } });

      await state.refreshParams({ filter: { tag: 'foo' } }, 1);
      expect(state.refreshCount).toBe(1);

      await state.refreshParams({ filter: { tag: 'foo' } }, 1);
      expect(state.refreshCount).toBe(1); // fails before fix
    });

    test('reloads when filter value changes', async () => {
      const state = new TestState({ filter: { tag: 'foo' } });

      await state.refreshParams({ filter: { tag: 'foo' } }, 1);
      expect(state.refreshCount).toBe(1);

      await state.refreshParams({ filter: { tag: 'bar' } }, 1);
      expect(state.refreshCount).toBe(2);
    });
  });

  /**
   * `refresh()` empties the list before it asks for anything, which is right
   * when the user has changed what they are looking at — the old results are
   * wrong and should go. It is the wrong shape for a background catch-up, where
   * the results on screen are still valid and only need reconciling: the list
   * disappears, a spinner takes its place, and the reader waits for a request
   * they never asked for.
   */
  describe('revalidate', () => {
    test('keeps the current items on screen while the request is outstanding', async () => {
      const state = new DeferredState();
      state.seed(20);

      const done = state.revalidate();

      expect(state.itemIds()).toHaveLength(20);
      expect(state.isInitialLoading()).toBe(false);
      expect(state.isLoading()).toBe(false);

      await state.settle(20, 'fresh');
      await done;
    });

    test('replaces the items once the response arrives', async () => {
      const state = new DeferredState();
      state.seed(2);
      expect(state.itemIds()).toEqual(['seed0', 'seed1']);

      const done = state.revalidate();
      await state.settle(3, 'fresh');
      await done;

      expect(state.itemIds()).toEqual(['fresh0', 'fresh1', 'fresh2']);
    });

    test('requests the first page', async () => {
      const state = new DeferredState();
      state.seed(20);

      const done = state.revalidate();
      await state.settle(20);
      await done;

      expect(state.loadPageCalls).toEqual([1]);
    });

    test('does not stack concurrent revalidations', async () => {
      const state = new DeferredState();
      state.seed(20);

      const first = state.revalidate();
      const second = state.revalidate();

      expect(state.loadPageCalls).toEqual([1]);

      await state.settle(20);
      await Promise.all([first, second]);

      // Once settled, a later call is free to run again.
      state.revalidate();
      expect(state.loadPageCalls).toEqual([1, 1]);
    });

    test('leaves the list intact when the request fails', async () => {
      const state = new DeferredState();
      state.seed(2);

      const done = state.revalidate();
      await state.reject();

      // A failed background refresh must not be able to blank the page the
      // reader is already looking at.
      await expect(done).resolves.toBeUndefined();
      expect(state.itemIds()).toEqual(['seed0', 'seed1']);
      expect(state.isInitialLoading()).toBe(false);
    });

    test('a failed revalidation does not block the next one', async () => {
      const state = new DeferredState();
      state.seed(2);

      const first = state.revalidate();
      await state.reject();
      await first;

      const second = state.revalidate();
      expect(state.loadPageCalls).toEqual([1, 1]);

      await state.settle(1, 'fresh');
      await second;

      expect(state.itemIds()).toEqual(['fresh0']);
    });

    test('an empty result empties the list rather than keeping stale rows', async () => {
      const state = new DeferredState();
      state.seed(2);

      const done = state.revalidate();
      await state.settle(0);
      await done;

      expect(state.itemIds()).toEqual([]);
      expect(state.isEmpty()).toBe(true);
    });
  });

  describe('superseded requests', () => {
    test('a slow response for previous params does not replace the current list', async () => {
      const state = new DeferredState();

      const first = state.refreshParams({ filter: { tag: 'a' } }, 1);
      const second = state.refreshParams({ filter: { tag: 'b' } }, 1);

      await state.settle(1, 'b', 1);
      await second;
      expect(state.itemIds()).toEqual(['b0']);

      await state.settle(3, 'a');
      await first;

      expect(state.itemIds()).toEqual(['b0']);
      expect(state.getParams()).toEqual({ filter: { tag: 'b' } });
      expect(state.isLoading()).toBe(false);
    });

    test('a superseded response does not end the loading state of the current request', async () => {
      const state = new DeferredState();

      const first = state.refreshParams({ filter: { tag: 'a' } }, 1);
      const second = state.refreshParams({ filter: { tag: 'b' } }, 1);

      await state.settle(3, 'a');
      await first;

      expect(state.isInitialLoading()).toBe(true);
      expect(state.itemIds()).toEqual([]);

      await state.settle(1, 'b');
      await second;

      expect(state.itemIds()).toEqual(['b0']);
      expect(state.isLoading()).toBe(false);
    });

    test('a pending "load more" does not append to a refreshed list', async () => {
      const state = new DeferredState();
      state.seed(2);

      const more = state.loadNext();
      const refreshed = state.refresh();

      await state.settle(1, 'fresh', 1);
      await refreshed;

      await state.settle(2, 'old');
      await more;

      expect(state.itemIds()).toEqual(['fresh0']);
      expect(state.isLoadingNext()).toBe(false);
    });

    test('a pending "load more" does not leave loading stuck after goto()', async () => {
      const state = new DeferredState();
      state.seed(2);

      const more = state.loadNext();
      const jumped = state.goto(1);

      await state.settle(1, 'fresh', 1);
      await jumped;

      await state.settle(2, 'old');
      await more;

      expect(state.isLoadingNext()).toBe(false);
      expect(state.itemIds()).toEqual(['fresh0']);
    });

    test('a pending revalidation does not replace a refreshed list', async () => {
      const state = new DeferredState();
      state.seed(2);

      const revalidated = state.revalidate();
      const refreshed = state.refreshParams({ filter: { tag: 'b' } }, 1);

      await state.settle(1, 'fresh', 1);
      await refreshed;

      await state.settle(2, 'old');
      await revalidated;

      expect(state.itemIds()).toEqual(['fresh0']);
    });
  });
});
