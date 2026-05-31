import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';

beforeAll(() => bootstrapForum());

describe('DiscussionListState', () => {
  describe('requestParams', () => {
    // Regression test for #4583.
    //
    // Extenders (tags, subscriptions, …) mutate `params.filter` inside a
    // `requestParams` extender callback. If `requestParams` returns
    // `this.params.filter` by reference, those mutations leak back into the
    // stored state — and on the next mount `paramsChanged()` falsely reports
    // a change, wiping the paginated cache and resetting the list to page 1.
    test('does not leak this.params.filter to callers', () => {
      const state = new DiscussionListState({ filter: { tag: 'foo' } } as any);

      const out = state.requestParams() as { filter: Record<string, string> };
      out.filter.injectedByExtender = 'yes';

      expect((state as any).params.filter).toEqual({ tag: 'foo' });
      expect((state as any).params.filter.injectedByExtender).toBeUndefined();
    });
  });
});
