import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import User from 'flarum/common/models/User';
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

/** A resolved save carrying the actor's count, as the API sends it in meta. */
const savedWithCount = (post: Post, actorLikeCount: number) => Object.assign(post, { payload: { meta: { actorLikeCount } } }) as any;

// Posts can arrive without their likes, e.g. from a list that named its own
// includes, which turns off the API's default ones.
describe('the like button', () => {
  it('likes a post that arrived without its likes and reads the count from the response', async () => {
    const user = app.session.user!;
    user.pushAttributes({ likeCount: 0 });
    const post = postWithoutLikes('71');
    const save = jest.spyOn(post, 'save').mockResolvedValue(savedWithCount(post, 1));
    const request = jest.spyOn(app, 'request');

    const saving = likeButton(post).attrs.onclick();
    expect(save).toHaveBeenCalledWith({ isLiked: true }, expect.objectContaining({ modifyText: expect.any(Function) }));
    expect((post as any).likes()).toEqual([user]);
    await saving;
    expect(user.likeCount()).toBe(1);
    // One request is enough: the mutation returns the count in its meta.
    expect(request).not.toHaveBeenCalled();
  });

  it('unlikes an already-liked post and reads the count from the response', async () => {
    const user = app.session.user!;
    user.pushAttributes({ likeCount: 1 });
    const post = postWithoutLikes('72');
    post.pushData({ relationships: { likes: { data: [{ type: 'users', id: user.id() }] } } } as any);
    const save = jest.spyOn(post, 'save').mockResolvedValue(savedWithCount(post, 0));
    const request = jest.spyOn(app, 'request');

    await likeButton(post).attrs.onclick();

    expect(save).toHaveBeenCalledWith({ isLiked: false }, expect.objectContaining({ modifyText: expect.any(Function) }));
    expect(user.likeCount()).toBe(0);
    expect(request).not.toHaveBeenCalled();
    expect((post as any).likes()).toEqual([]);
  });

  it.each([
    { liked: false, from: 0, to: 1 },
    { liked: true, from: 1, to: 0 },
  ])('updates the rendered profile count from $from to $to from the response meta', async ({ liked, from, to }) => {
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

    let finishSave!: () => void;
    const saved = new Promise<void>((resolve) => {
      finishSave = resolve;
    });
    jest.spyOn(post, 'save').mockImplementation(async () => {
      await saved;
      return savedWithCount(post, to);
    });
    const redrawSync = m.redraw.sync;
    jest.spyOn(globalThis.m, 'redraw').mockImplementation(redrawSync);
    try {
      const saving = likeButton(post).attrs.onclick();
      await Promise.resolve();
      expect(badge()).toBe(String(from));

      finishSave();
      await saving;
      expect(badge()).toBe(String(to));
    } finally {
      jest.restoreAllMocks();
      m.mount(root, null);
      root.remove();
    }
  });

  it('discards an older count that arrives after a newer one', async () => {
    const user = app.session.user!;
    user.pushAttributes({ likeCount: 2 });
    const olderPost = postWithoutLikes('75');
    const newerPost = postWithoutLikes('76');

    let finishOlder!: () => void;
    const olderGate = new Promise<void>((resolve) => {
      finishOlder = resolve;
    });

    // Answer like the API does, through the real Model.save: each response is
    // a whole payload, and the actor comes back in `included` with the count
    // read for that request.
    jest.spyOn(app, 'request').mockImplementation(((options: any) => {
      const postId = options.body.data.id;
      const actorLikeCount = postId === olderPost.id() ? 3 : 4;
      const payload = {
        data: {
          type: 'posts',
          id: postId,
          attributes: { number: 1, contentType: 'comment', canLike: true, isHidden: false },
          relationships: { likes: { data: [{ type: 'users', id: user.id() }] } },
        },
        included: [{ type: 'users', id: user.id(), attributes: { likeCount: actorLikeCount } }],
        meta: { actorLikeCount },
      };

      return (postId === olderPost.id() ? olderGate : Promise.resolve()).then(() => {
        const text = JSON.stringify(payload);
        // The real request pipeline edits the raw text before parsing it.
        return JSON.parse(options.modifyText ? options.modifyText(text) : text);
      });
    }) as any);

    const older = likeButton(olderPost).attrs.onclick();
    await Promise.resolve();
    const newer = likeButton(newerPost).attrs.onclick();
    await newer;
    expect(user.likeCount()).toBe(4);

    finishOlder();
    await older;
    expect(user.likeCount()).toBe(4);
  });

  it('leaves the count alone when the response has no meta', async () => {
    const user = app.session.user!;
    user.pushAttributes({ likeCount: 3 });
    const post = postWithoutLikes('77');
    jest.spyOn(post, 'save').mockResolvedValue(post as any);

    await likeButton(post).attrs.onclick();

    expect(user.likeCount()).toBe(3);
  });
});

describe('the likes tab on a profile', () => {
  it('renders no badge when the API did not send a count', () => {
    app.store.pushPayload({
      data: { type: 'users', id: '81', attributes: { username: 'someone' } },
    } as any);
    const user = app.store.getById<User>('users', '81')!;

    const root = document.createElement('div');
    document.body.append(root);
    const profile = new UserPage();
    profile.user = user;
    m.mount(root, { view: () => profile.navItems().get('likes') });

    try {
      expect(root.querySelector('.Button-badge')).toBeNull();
    } finally {
      m.mount(root, null);
      root.remove();
    }
  });
});
