import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import PostTypes from '../../../../src/common/extenders/PostTypes';
import PostType from '../../../../src/forum/components/PostType';
import EventPost from '../../../../src/forum/components/EventPost';
import Post from '../../../../src/common/models/Post';
import mq from 'mithril-query';
import app from '@flarum/core/src/forum/app';

beforeAll(() => bootstrapForum());

describe('PostTypes extender', () => {
  app.store.pushPayload({
    data: {
      id: '1',
      type: 'discussions',
      attributes: {
        title: 'Discussion title',
      },
      relationships: {
        posts: {
          data: [
            { id: '1', type: 'posts' },
            { id: '2', type: 'posts' },
            { id: '3', type: 'posts' },
            { id: '4', type: 'posts' },
          ],
        },
      },
    },
  });

  const keanuPost = new Post({
    id: '1',
    type: 'posts',
    attributes: { contentType: 'keanu', canEdit: false, createdAt: new Date(), contentHtml: '<strong>Hi</strong>' },
    relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
  });

  const commentPost = new Post({
    id: '2',
    type: 'posts',
    attributes: { contentType: 'comment', canEdit: false, createdAt: new Date(), contentHtml: '<strong>Bye</strong>' },
    relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
  });

  it('comment posts work by default', () => {
    app.boot();

    const postComponent = mq(PostType, {
      post: commentPost,
    });

    expect(postComponent).toContainRaw('Bye');
  });

  it('does not work before registering the post type', () => {
    app.boot();

    const postComponent = mq(PostType, {
      post: keanuPost,
    });

    expect(postComponent).not.toContainRaw('Hi');
  });

  it('works after registering the post type', () => {
    app.bootExtensions({
      test: {
        extend: [new PostTypes().add('keanu', KeanuPost)],
      },
    });

    app.boot();

    const postComponent = mq(PostType, {
      post: keanuPost,
    });

    expect(postComponent).toContainRaw('Hi');
  });
});

class KeanuPost extends EventPost {
  icon() {
    return 'fas fa-keanu';
  }

  description() {
    return this.attrs.post.contentHtml();
  }
}
