import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import CommentPost from 'flarum/forum/components/CommentPost';
import UserPage from 'flarum/forum/components/UserPage';
import { jest } from '@jest/globals';
import m from 'mithril';
import extenders from '../../../src/forum/extend';
import addLikeAction from '../../../src/forum/addLikeAction';
import addLikesTabToUserProfile from '../../../src/forum/addLikesTabToUserProfile';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  extenders.forEach((extender: any) => extender.extend(app, { name: 'flarum-likes', exports: {} }));
  addLikeAction();
  addLikesTabToUserProfile();
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
  it('likes a post that arrived without its likes', async () => {
    const post = postWithoutLikes('71');
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(app.session.user as any);

    expect(() => likeButton(post).attrs.onclick()).not.toThrow();
    expect(save).toHaveBeenCalledWith({ isLiked: true });
    expect((post as any).likes()).toEqual([app.session.user]);
    await Promise.resolve();
    expect(find).toHaveBeenCalledWith('users', app.session.user.id());
  });

  it('refreshes an already-loaded user after unliking', async () => {
    const post = postWithoutLikes('72');
    post.pushData({ relationships: { likes: { data: [{ type: 'users', id: app.session.user.id() }] } } } as any);
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);
    const find = jest.spyOn(app.store, 'find').mockResolvedValue(app.session.user as any);

    likeButton(post).attrs.onclick();
    await Promise.resolve();

    expect(save).toHaveBeenCalledWith({ isLiked: false });
    expect(find).toHaveBeenCalledWith('users', app.session.user.id());
    expect((post as any).likes()).toEqual([]);
  });

  it.each([
    { liked: false, from: 0, to: 1 },
    { liked: true, from: 1, to: 0 },
  ])('updates the rendered profile count from $from to $to after the user refresh', async ({ liked, from, to }) => {
    const user = app.session.user!;
    const post = postWithoutLikes(liked ? '73' : '74');
    user.pushAttributes({ likeCount: from });
    if (liked) {
      post.pushData({ relationships: { likes: { data: [{ type: 'users', id: user.id() }] } } } as any);
    }

    const root = document.createElement('div');
    document.body.append(root);
    const profile = new UserPage();
    profile.user = user;
    m.mount(root, { view: () => profile.navItems().get('likes') });
    const badge = () => root.querySelector('.Button-badge')?.textContent;

    jest.spyOn(post, 'save').mockResolvedValue(post as any);
    let finishRefresh!: () => void;
    const refresh = new Promise<void>((resolve) => {
      finishRefresh = resolve;
    });
    jest.spyOn(app.store, 'find').mockImplementation(async () => {
      await refresh;
      user.pushAttributes({ likeCount: to });
      return user as any;
    });
    const redrawSync = m.redraw.sync;
    jest.spyOn(globalThis.m, 'redraw').mockImplementation(redrawSync);
    try {
      const saved = likeButton(post).attrs.onclick();
      await Promise.resolve();
      expect(badge()).toBe(String(from));

      finishRefresh();
      await saved;
      expect(badge()).toBe(String(to));
    } finally {
      jest.restoreAllMocks();
      m.mount(root, null);
      root.remove();
    }
  });
});
