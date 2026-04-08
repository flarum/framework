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
});
