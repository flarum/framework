import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import PostStream from '../../../../src/forum/components/PostStream';
import PostStreamState from '../../../../src/forum/states/PostStreamState';
import Discussion from '../../../../src/common/models/Discussion';
import app from '../../../../src/forum/app';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('PostStream component', () => {
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

  app.store.pushPayload({
    data: [
      {
        id: '1',
        type: 'posts',
        attributes: { contentType: 'comment', canEdit: false, createdAt: new Date(), contentHtml: '<strong>Hi</strong>' },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '2',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Bye</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '3',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Hi again</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
      {
        id: '4',
        type: 'posts',
        attributes: {
          contentType: 'comment',
          canEdit: false,
          createdAt: new Date(),
          contentHtml: '<strong>Bye again</strong>',
        },
        relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
      },
    ],
  });

  const discussion = app.store.getById<Discussion>('discussions', '1');

  it('renders correctly', () => {
    app.boot();

    const postStream = mq(PostStream, {
      stream: new PostStreamState(discussion, app.store.all('posts')),
      discussion,
    });

    expect(postStream).toContainRaw('Hi');
    expect(postStream).toContainRaw('Bye');
    expect(postStream).toContainRaw('Hi again');
    expect(postStream).toContainRaw('Bye again');
  });
});
