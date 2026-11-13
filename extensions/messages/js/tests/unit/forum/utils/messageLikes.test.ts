import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { jest } from '@jest/globals';
import extractText from 'flarum/common/utils/extractText';
import Dialog from '../../../../src/common/models/Dialog';
import DialogMessage from '../../../../src/common/models/DialogMessage';
import { likeButton, likedBy, toggleLike } from '../../../../src/forum/utils/messageLikes';

const ME = '1';

beforeAll(() => {
  bootstrapForum();
  app.boot();

  (app.store.models as any).dialogs = Dialog;
  (app.store.models as any)['dialog-messages'] = DialogMessage;

  app.translator.addTranslations({
    'flarum-likes.forum.post.like_link': 'Like',
    'flarum-likes.forum.post.unlike_link': 'Unlike',
    'flarum-likes.forum.post.you_text': 'You',
    'flarum-likes.forum.post.others_link': '{count, plural, one {# other} other {# others}}',
    'flarum-likes.forum.post.liked_by_text': '{count, plural, one {{users} likes this} other {{users} like this}}.',
    'flarum-likes.forum.post.liked_by_self_text': '{users} like this.',
  });

  for (const id of ['2', '3', '4', '5', '6']) {
    app.store.pushObject({ type: 'users', id, attributes: { username: `user${id}`, displayName: `User ${id}` } } as any);
  }
});

afterEach(() => jest.restoreAllMocks());

let nextId = 500;

function message(likers: string[], attributes: Record<string, unknown> = {}): DialogMessage {
  return app.store.pushObject<DialogMessage>({
    type: 'dialog-messages',
    id: String(nextId++),
    attributes: { type: 'comment', canLike: true, likesCount: likers.length, ...attributes },
    relationships: { likes: { data: likers.map((id) => ({ type: 'users', id })) } },
  } as any)!;
}

const likerIds = (m: DialogMessage) => (m.likes() || []).map((user) => user && user.id());

describe('likeButton', () => {
  it('is not offered on a message the member cannot like', () => {
    expect(likeButton(message([], { canLike: false }))).toBeNull();
  });

  it('is not offered where the forum sends nothing about likes', () => {
    expect(likeButton(message([], { canLike: undefined }))).toBeNull();
  });

  it('still lets a member take back a like they can no longer give', () => {
    expect(extractText(likeButton(message(['2', ME], { canLike: false }))!.children as any)).toBe('Unlike');
  });

  it('says Like, or Unlike once the member has liked it', () => {
    expect(extractText(likeButton(message(['2']))!.children as any)).toBe('Like');
    expect(extractText(likeButton(message(['2', ME]))!.children as any)).toBe('Unlike');
  });
});

describe('toggleLike', () => {
  it('likes at once, the member first, and asks the forum to', async () => {
    const m = message(['2']);
    const request = jest.spyOn(app, 'request').mockResolvedValue({ data: { type: 'dialog-messages', id: m.id(), attributes: {} } } as never);

    const done = toggleLike(m);

    expect(likerIds(m)).toEqual([ME, '2']);
    expect(m.likesCount()).toBe(2);
    expect(request).toHaveBeenCalledWith(expect.objectContaining({ method: 'POST', url: expect.stringMatching(new RegExp(`/dialog-messages/${m.id()}/like$`)) }));

    await done;
  });

  it('takes the like back at once, and asks the forum to', async () => {
    const m = message([ME, '2']);
    const request = jest.spyOn(app, 'request').mockResolvedValue({ data: { type: 'dialog-messages', id: m.id(), attributes: {} } } as never);

    await toggleLike(m);

    expect(likerIds(m)).toEqual(['2']);
    expect(m.likesCount()).toBe(1);
    expect(request).toHaveBeenCalledWith(expect.objectContaining({ method: 'DELETE' }));
  });

  it('keeps what the forum answers', async () => {
    const m = message([]);
    jest.spyOn(app, 'request').mockResolvedValue({
      data: {
        type: 'dialog-messages',
        id: m.id(),
        attributes: { likesCount: 3 },
        relationships: { likes: { data: ['1', '3', '4'].map((id) => ({ type: 'users', id })) } },
      },
    } as never);

    await toggleLike(m);

    expect(likerIds(m)).toEqual([ME, '3', '4']);
    expect(m.likesCount()).toBe(3);
  });

  it('ignores another click while the first is on its way', async () => {
    const m = message(['2']);
    let answer!: (payload: unknown) => void;
    const request = jest.spyOn(app, 'request').mockReturnValue(new Promise((resolve) => (answer = resolve)) as never);

    const first = toggleLike(m);
    const second = toggleLike(m);

    expect(request).toHaveBeenCalledTimes(1);
    expect(likerIds(m)).toEqual([ME, '2']);

    answer({ data: { type: 'dialog-messages', id: m.id(), attributes: {} } });
    await Promise.all([first, second]);

    // Once it is settled, the next click goes through.
    request.mockResolvedValue({ data: { type: 'dialog-messages', id: m.id(), attributes: {} } } as never);
    await toggleLike(m);

    expect(request).toHaveBeenCalledTimes(2);
    expect(request).toHaveBeenLastCalledWith(expect.objectContaining({ method: 'DELETE' }));
  });

  it('lets the member try again after the forum refused', async () => {
    const m = message([]);
    const request = jest.spyOn(app, 'request').mockRejectedValueOnce(new Error('500') as never);

    await expect(toggleLike(m)).rejects.toThrow('500');

    request.mockResolvedValue({ data: { type: 'dialog-messages', id: m.id(), attributes: {} } } as never);
    await toggleLike(m);

    expect(request).toHaveBeenCalledTimes(2);
  });

  it('puts things back when the forum refuses', async () => {
    const m = message(['2']);
    jest.spyOn(app, 'request').mockRejectedValue(new Error('403') as never);

    await expect(toggleLike(m)).rejects.toThrow('403');

    expect(likerIds(m)).toEqual(['2']);
    expect(m.likesCount()).toBe(1);
  });
});

describe('likedBy', () => {
  it('shows nothing for a message nobody liked', () => {
    expect(likedBy(message([]))).toBeNull();
  });

  it('names the likers, the member first as You', () => {
    expect(extractText(likedBy(message(['2', ME]))!.children as any)).toBe('You and User 2 like this.');
    expect(extractText(likedBy(message(['2']))!.children as any)).toBe('User 2 likes this.');
  });

  it('counts the likers it does not name', () => {
    // Four listed, of six.
    const m = message(['2', '3', '4', '5'], { likesCount: 6 });

    expect(extractText(likedBy(m)!.children as any)).toBe('User 2, User 3, User 4, and 3 others like this.');
  });
});
