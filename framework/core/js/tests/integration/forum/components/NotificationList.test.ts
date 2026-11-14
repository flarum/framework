import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import NotificationList from '../../../../src/forum/components/NotificationList';
import NotificationListState from '../../../../src/forum/states/NotificationListState';
import type Notification from '../../../../src/common/models/Notification';
import { makeDiscussion } from '../../../factory';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

const mounted: HTMLElement[] = [];

beforeEach(() => {
  app.store.data = {};
});

afterEach(() => {
  // Unmounting runs `onremove`, which detaches the list's window scroll listener.
  mounted.splice(0).forEach((root) => {
    m.mount(root, null);
    root.remove();
  });
});

/**
 * Stands in for the API: each page load waits on a promise the test settles,
 * so a test can look at the list while a page is still on its way.
 */
class TestState extends NotificationListState {
  public requests: Array<{ page: number; resolve: (items: Notification[], hasNext: boolean) => void }> = [];

  protected loadPage(page = 1): Promise<any> {
    return new Promise((resolve) => {
      this.requests.push({
        page,
        resolve: (items, hasNext) => resolve(Object.assign(items.slice(), { payload: { links: hasNext ? { next: '/api/notifications' } : {}, meta: {} } })),
      });
    });
  }
}

/**
 * Mount into the real document rather than through mithril-query: the list's
 * scroll handling reads computed styles, which need real elements.
 */
function mount(state: NotificationListState): HTMLElement {
  const root = document.createElement('div');
  document.body.appendChild(root);
  mounted.push(root);

  m.mount(root, { view: () => m(NotificationList, { state }) });

  return root;
}

function pushNotification(id: string): Notification {
  app.store.pushObject(makeDiscussion({ id }));

  return app.store.pushObject<Notification>({
    type: 'notifications',
    id,
    attributes: { contentType: 'discussionRenamed', content: { postNumber: 1 }, isRead: false, createdAt: new Date().toISOString() },
    relationships: { subject: { data: { type: 'discussions', id } } },
  })!;
}

describe('NotificationList', () => {
  test('renders the loaded notifications', async () => {
    const state = new TestState();

    const firstPage = state.loadNext();
    state.requests[0].resolve([pushNotification('1'), pushNotification('2')], true);
    await firstPage;

    const list = mount(state);

    expect(list.querySelectorAll('.Notification')).toHaveLength(2);
  });

  test('shows only a loading indicator while the first page loads', () => {
    const state = new TestState();

    state.loadNext();

    const list = mount(state);

    expect(list.querySelector('.LoadingIndicator')).not.toBeNull();
    expect(list.querySelector('.HeaderList-empty')).toBeNull();
  });

  test('scrolling to the end of the list requests the next page', async () => {
    const state = new TestState();

    const firstPage = state.loadNext();
    state.requests[0].resolve([pushNotification('1'), pushNotification('2')], true);
    await firstPage;

    mount(state);
    window.dispatchEvent(new Event('scroll'));

    expect(state.requests.map((request) => request.page)).toEqual([1, 2]);
  });

  test('does not request another page once the last one has loaded', async () => {
    const state = new TestState();

    const firstPage = state.loadNext();
    state.requests[0].resolve([pushNotification('1'), pushNotification('2')], false);
    await firstPage;

    mount(state);
    window.dispatchEvent(new Event('scroll'));

    expect(state.requests.map((request) => request.page)).toEqual([1]);
  });

  test('keeps the loaded notifications on screen while the next page loads', async () => {
    const state = new TestState();

    const firstPage = state.loadNext();
    state.requests[0].resolve([pushNotification('1'), pushNotification('2')], true);
    await firstPage;

    state.loadNext();

    const list = mount(state);

    expect(list.querySelectorAll('.Notification')).toHaveLength(2);
    expect(list.querySelector('.LoadingIndicator')).not.toBeNull();
  });
});
