import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import { jest } from '@jest/globals';
import extenders from '../../../src/forum/extend';
import addLikeAction from '../../../src/forum/addLikeAction';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  extenders.forEach((extender: any) => extender.extend(app, { name: 'flarum-likes', exports: {} }));
  addLikeAction();
});

afterEach(() => jest.restoreAllMocks());

/** A post as a list that didn't ask for its likes has it. */
function postWithoutLikes(id: string): Post {
  app.store.pushPayload({
    data: { type: 'posts', id, attributes: { number: 1, contentType: 'comment', canLike: true, isHidden: false }, relationships: {} },
  } as any);

  return app.store.getById<Post>('posts', id)!;
}

const likeButton = (post: Post) => CommentPost.prototype.actionItems.call({ attrs: { post } } as any).get('like') as any;

// Posts can arrive without their likes, e.g. from a list that named its own
// includes, which turns off the API's default ones.
describe('the like button', () => {
  it('likes a post that arrived without its likes', () => {
    const post = postWithoutLikes('71');
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);

    expect(() => likeButton(post).attrs.onclick()).not.toThrow();
    expect(save).toHaveBeenCalledWith({ isLiked: true });
    expect((post as any).likes()).toEqual([app.session.user]);
  });
});
