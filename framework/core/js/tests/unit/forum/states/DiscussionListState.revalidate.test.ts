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
 * `revalidate()` is the shape the realtime reconnect catch-up uses: it reloads
 * the first page without taking the current results off screen, so unlike
 * `refresh()` it never routes through `clear()` and never empties
 * `extraDiscussions`.
 *
 * That leaves the two halves of the list to reconcile. Realtime moves a
 * discussion into `extraDiscussions`, and the page the catch-up fetches
 * contains that same discussion — a new post is what put it at the top in the
 * first place — so without reconciliation it renders from both halves at once.
 * A reader returning to an idle tab sees it listed twice until they refresh.
 */
class TestState extends DiscussionListState {
  /** What the next page load resolves with. */
  public nextPage: Discussion[] = [];

  /** When set, the next page load rejects instead. */
  public failNextLoad = false;

  protected loadPage(): Promise<any> {
    if (this.failNextLoad) return Promise.reject(new Error('network'));

    return Promise.resolve(Object.assign(this.nextPage.slice(), { payload: { links: {}, meta: {} } }));
  }

  /** Stand in for an initial page load holding these discussions. */
  public seed(items: Discussion[]): void {
    (this as any).pages = [{ number: 1, items, hasNext: false, hasPrev: false }];
    (this as any).initialLoading = false;
  }

  /** The ids the list actually renders, in order. */
  public ids(): string[] {
    return this.getPages()
      .flatMap((page) => page.items as Discussion[])
      .map((discussion) => discussion.id()!);
  }
}

function push(id: string): Discussion {
  return app.store.pushObject<Discussion>(makeDiscussion({ id }))!;
}

describe('DiscussionListState reconciles realtime additions on revalidate', () => {
  test('a discussion realtime added is not duplicated when the catch-up refetches it', async () => {
    const a = push('1');
    const b = push('2');

    const state = new TestState({});
    state.seed([b, a]);

    // Realtime releases its pending updates: `a` leaves `pages` for
    // `extraDiscussions` and renders at the top.
    state.addDiscussion(a);
    expect(state.ids()).toEqual(['1', '2']);

    // The tab was idle, the socket died, the reader came back. The catch-up
    // revalidates, and the server sorts `a` first because of the post that
    // triggered the realtime event in the first place.
    state.nextPage = [a, b];
    await state.revalidate();

    expect(state.ids()).toEqual(['1', '2']);
  });

  test('a realtime addition the refetch did not return stays on the list', async () => {
    const a = push('1');
    const b = push('2');

    const state = new TestState({});
    state.seed([b]);
    state.addDiscussion(a);

    // The reloaded page does not carry `a` — it is filtered out of this list,
    // say. Reconciling must not be a blanket clear.
    state.nextPage = [b];
    await state.revalidate();

    expect(state.ids()).toEqual(['1', '2']);
  });

  test('a failed revalidation leaves the list, realtime additions included, untouched', async () => {
    const a = push('1');
    const b = push('2');

    const state = new TestState({});
    state.seed([b]);
    state.addDiscussion(a);

    // `revalidate()` swallows the failure and keeps what is on screen; the
    // pages were never replaced, so nothing may be dropped from either half.
    state.failNextLoad = true;
    await state.revalidate();

    expect(state.ids()).toEqual(['1', '2']);
  });

  test('refresh still empties extraDiscussions, as it always has', async () => {
    const a = push('1');
    const b = push('2');

    const state = new TestState({});
    state.seed([b, a]);
    state.addDiscussion(a);

    state.nextPage = [a, b];
    await state.refresh();

    expect(state.ids()).toEqual(['1', '2']);
  });
});
