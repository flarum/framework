import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import { jest } from '@jest/globals';
import addPostReplyAction from '../../../src/forum/addPostReplyAction';
import PostQuoteButton from '../../../src/forum/fragments/PostQuoteButton';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  addPostReplyAction();

  app.session.user = null;
});

beforeEach(() => {
  jest.spyOn(app.modal, 'show').mockImplementation(() => {});
});

afterEach(() => jest.restoreAllMocks());

// Jest fails the test if a rejection goes unhandled, which Node reports only
// once the microtask queue has drained.
const settle = () => new Promise((resolve) => setTimeout(resolve, 10));

function post(): Post {
  app.store.pushPayload({
    data: {
      type: 'posts',
      id: '1',
      attributes: { number: 1, contentType: 'comment', isHidden: false },
      relationships: { discussion: { data: { type: 'discussions', id: '1' } } },
    },
    included: [{ type: 'discussions', id: '1', attributes: {} }],
  } as any);

  return app.store.getById<Post>('posts', '1')!;
}

// Replying as a guest asks them to log in, which rejects the reply.
describe('replying to a post as a guest', () => {
  test('the reply link asks them to log in, and nothing more', async () => {
    const items = CommentPost.prototype.actionItems.call({ attrs: { post: post() } } as any);

    items.get('reply').attrs.onclick();

    await settle();

    expect(app.modal.show).toHaveBeenCalled();
  });

  test('the quote button asks them to log in, and nothing more', async () => {
    const button = new PostQuoteButton(post()) as any;
    button.content = 'Some text to quote';

    button.view().attrs.onclick();

    await settle();

    expect(app.modal.show).toHaveBeenCalled();
  });
});
