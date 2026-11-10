import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Notification from 'flarum/common/models/Notification';
import PostLikedNotification from '../../../src/forum/components/PostLikedNotification';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  // jsdom has no innerText, which a post's plain content is read from.
  if (!('innerText' in HTMLElement.prototype)) {
    Object.defineProperty(HTMLElement.prototype, 'innerText', {
      get(this: HTMLElement) {
        return this.textContent;
      },
    });
  }

  app.store.pushPayload({
    data: { type: 'discussions', id: '1', attributes: { slug: '1-a-discussion', title: 'A discussion' } },
  } as any);
});

function likedNotification(post: { id: string; attributes: Record<string, unknown> }) {
  app.store.pushPayload({
    data: { type: 'posts', ...post, relationships: { discussion: { data: { type: 'discussions', id: '1' } } } },
  } as any);

  return new Notification(
    {
      id: post.id,
      type: 'notifications',
      attributes: { contentType: 'postLiked', content: null, createdAt: new Date(), isRead: false },
      relationships: { subject: { data: { type: 'posts', id: post.id } } },
    },
    app.store
  );
}

/** What the notification renders, as it appears in the notification list. */
function render(notification: Notification) {
  const root = document.createElement('div');

  m.render(root, m(PostLikedNotification, { notification }));

  return root.querySelector('.Notification');
}

describe('the post liked notification', () => {
  it('shows an excerpt of a liked comment', () => {
    const notification = likedNotification({
      id: '10',
      attributes: { number: 2, contentType: 'comment', contentHtml: '<p>The liked comment</p>' },
    });

    expect(render(notification)?.textContent).toContain('The liked comment');
  });

  // The API accepts a like on any post type, and other types have no text. A
  // notification that can't render takes the whole notification list with it.
  it('renders for a liked post that has no text', () => {
    const notification = likedNotification({
      id: '11',
      attributes: { number: 3, contentType: 'discussionRenamed', content: ['Old title', 'New title'] },
    });

    expect(render(notification)).not.toBeNull();
  });
});
