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
const countResponse = (likeCount: number) => ({ data: { attributes: { likeCount } } });

// Posts can arrive without their likes, e.g. from a list that named its own
// includes, which turns off the API's default ones.
describe('the like button', () => {
  it('likes a post that arrived without its likes', async () => {
    const post = postWithoutLikes('71');
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);
    const request = jest.spyOn(app, 'request').mockResolvedValue(countResponse(1));

    expect(() => likeButton(post).attrs.onclick()).not.toThrow();
    expect(save).toHaveBeenCalledWith({ isLiked: true }, expect.any(Object));
    expect((post as any).likes()).toEqual([app.session.user]);
    await Promise.resolve();
    expect(request).toHaveBeenCalledWith(expect.objectContaining({ url: expect.stringContaining('/users/' + app.session.user.id()) }));
  });

  it('refreshes an already-loaded user after unliking', async () => {
    const post = postWithoutLikes('72');
    post.pushData({ relationships: { likes: { data: [{ type: 'users', id: app.session.user.id() }] } } } as any);
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);
    const request = jest.spyOn(app, 'request').mockResolvedValue(countResponse(0));

    likeButton(post).attrs.onclick();
    await Promise.resolve();

    expect(save).toHaveBeenCalledWith({ isLiked: false }, expect.any(Object));
    expect(request).toHaveBeenCalledWith(expect.objectContaining({ url: expect.stringContaining('/users/' + app.session.user.id()) }));
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
    jest.spyOn(app, 'request').mockImplementation(async () => {
      await refresh;
      return countResponse(to) as any;
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

  it('discards an older count response delivered after a newer one', async () => {
    const user = app.session.user!;
    user.pushAttributes({ likeCount: 2 });
    const firstPost = postWithoutLikes('75');
    const secondPost = postWithoutLikes('76');
    for (const post of [firstPost, secondPost]) {
      post.pushData({ relationships: { likes: { data: [{ type: 'users', id: user.id() }] } } } as any);
      jest.spyOn(post, 'save').mockResolvedValue(post as any);
    }

    let finishOlder!: (value: ReturnType<typeof countResponse>) => void;
    const older = new Promise<ReturnType<typeof countResponse>>((resolve) => {
      finishOlder = resolve;
    });
    const request = jest
      .spyOn(app, 'request')
      .mockImplementationOnce(() => older as any)
      .mockResolvedValueOnce(countResponse(0));

    const first = likeButton(firstPost).attrs.onclick();
    await Promise.resolve();
    const second = likeButton(secondPost).attrs.onclick();
    await second;
    expect(request).toHaveBeenCalledTimes(2);
    expect(user.likeCount()).toBe(0);

    finishOlder(countResponse(1));
    await first;
    expect(user.likeCount()).toBe(0);
  });

  it('keeps a mutation response from overwriting the actor’s count', async () => {
    const user = app.session.user!;
    const post = postWithoutLikes('77');
    user.pushAttributes({ likeCount: 5 });
    jest.spyOn(app, 'request').mockResolvedValue(countResponse(5));
    const save = jest.spyOn(post, 'save').mockResolvedValue(post as any);

    await likeButton(post).attrs.onclick();
    const modify = save.mock.calls[0][1]?.modifyText!;
    const included = [
      { type: 'users', id: user.id(), attributes: { username: 'Actor', likeCount: 4 } },
      { type: 'users', id: '999', attributes: { likeCount: 8 } },
    ];
    const payload = JSON.parse(modify(JSON.stringify({ included })));

    expect(payload.included[0].attributes).toEqual({ username: 'Actor' });
    expect(payload.included[1]).toEqual(included[1]);
  });
});
