import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import DiscussionListState from '../../../../src/forum/states/DiscussionListState';
import Discussion from '../../../../src/common/models/Discussion';
import { makeDiscussion } from '../../../factory';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

beforeEach(() => {
  app.store.data = {};
});

/**
 * Adding and removing discussions from the list must identify a discussion by
 * its id, not by object reference. Realtime hands the list a discussion it got
 * from `app.store.pushPayload`, which returns a fresh model instance whenever
 * the store no longer holds that discussion — so a reference-based check misses
 * the copy already on screen and the discussion shows twice until a refresh.
 * (Reported against rc.7.)
 */
describe('DiscussionListState de-duplicates by discussion id', () => {
  function seedListWith(discussion: Discussion): DiscussionListState {
    const state = new DiscussionListState({});
    // Stand in for an initial page load holding this discussion.
    (state as any).pages = [{ number: 0, items: [discussion] }];
    return state;
  }

  function idsInList(state: DiscussionListState): string[] {
    return state
      .getPages()
      .flatMap((page) => page.items as Discussion[])
      .map((d) => d.id()!);
  }

  describe('with realtime (addDiscussion)', () => {
    test('re-adding the same instance does not duplicate', () => {
      const a = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      const state = seedListWith(a);

      // The store still holds it, so realtime would hand back the same object.
      state.addDiscussion(a);

      expect(idsInList(state).filter((id) => id === '1')).toHaveLength(1);
    });

    test('re-adding a fresh instance of the same discussion does not duplicate', () => {
      const a = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      const state = seedListWith(a);

      // The store no longer holds it (evicted / scrolled out), so a realtime
      // event builds a brand-new instance for the same id.
      app.store.data = {};
      const b = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;

      expect(b).not.toBe(a);

      state.addDiscussion(b);

      expect(idsInList(state).filter((id) => id === '1')).toHaveLength(1);
    });

    test('the re-added discussion moves to the top', () => {
      const a = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      const other = app.store.pushObject<Discussion>(makeDiscussion({ id: '2' }))!;
      const state = new DiscussionListState({});
      (state as any).pages = [{ number: 0, items: [other, a] }];

      app.store.data = {};
      const fresh = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      state.addDiscussion(fresh);

      // Once, and now first.
      expect(idsInList(state).filter((id) => id === '1')).toHaveLength(1);
      expect(idsInList(state)[0]).toBe('1');
    });
  });

  describe('without realtime (deletion flows call removeDiscussion)', () => {
    test('removing a discussion drops it from the page', () => {
      const a = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      const b = app.store.pushObject<Discussion>(makeDiscussion({ id: '2' }))!;
      const state = new DiscussionListState({});
      (state as any).pages = [{ number: 0, items: [a, b] }];

      // How PostControls / DiscussionControls remove a deleted discussion — the
      // canonical list instance, no realtime involved.
      state.removeDiscussion(a);

      expect(idsInList(state)).toEqual(['2']);
    });

    test('removing one extra discussion leaves the others after it in place', () => {
      // Guards the splice(index) -> splice(index, 1) fix: removing an item must
      // not take everything after it in the list with it.
      const a = app.store.pushObject<Discussion>(makeDiscussion({ id: '1' }))!;
      const b = app.store.pushObject<Discussion>(makeDiscussion({ id: '2' }))!;
      const c = app.store.pushObject<Discussion>(makeDiscussion({ id: '3' }))!;
      const state = new DiscussionListState({});
      // All three sitting in extraDiscussions, as realtime pushes accumulate.
      state.addDiscussion(c);
      state.addDiscussion(b);
      state.addDiscussion(a); // order is now [1, 2, 3]

      state.removeDiscussion(b);

      expect(idsInList(state)).toEqual(['1', '3']);
    });
  });
});
