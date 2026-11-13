import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import haptic from 'flarum/common/utils/haptic';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import username from 'flarum/common/helpers/username';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import type DialogMessage from '../../common/models/DialogMessage';

/**
 * Likes on messages, with flarum/likes enabled, in that extension's words.
 * A message names as many likers as the forum sends it (four) and counts the
 * rest: there is no list of them all, as there is for a post.
 */

const isLikedByMe = (message: DialogMessage): boolean => !!app.session.user && (message.likes() || []).includes(app.session.user);

export function likeButton(message: DialogMessage): Mithril.Vnode<any, any> | null {
  const liked = isLikedByMe(message);

  // A like can always be taken back, as the forum allows, even by a member
  // who may no longer give one.
  if (!message.canLike() && !liked) return null;

  return (
    <Button className="Button Button--link" onclick={() => toggleLike(message).catch(() => {})}>
      {app.translator.trans(liked ? 'flarum-likes.forum.post.unlike_link' : 'flarum-likes.forum.post.like_link')}
    </Button>
  );
}

/**
 * Likes the message, or takes the like back, showing it at once and keeping
 * what the forum answers; puts it back as it was if the forum refuses.
 */
/** Messages with a like or unlike on its way: clicks on them wait for it. */
const pending = new WeakSet<DialogMessage>();

export function toggleLike(message: DialogMessage): Promise<void> {
  // Otherwise a second click could send the opposite request alongside the
  // first, and whichever the forum handled last would win, whatever is shown.
  if (pending.has(message)) return Promise.resolve();

  const me = app.session.user!;
  const liking = !isLikedByMe(message);
  const before = { likes: message.data.relationships?.likes, likesCount: message.likesCount() };
  const others = (message.likes() || []).filter((user) => user && user !== me).map((user) => ({ type: 'users', id: (user as User).id()! }));

  if (liking) haptic('success');

  message.pushData({
    attributes: { likesCount: Math.max(0, (message.likesCount() ?? 0) + (liking ? 1 : -1)) },
    relationships: { likes: { data: liking ? [{ type: 'users', id: me.id()! }, ...others] : others } },
  } as any);

  pending.add(message);

  return app
    .request({
      method: liking ? 'POST' : 'DELETE',
      url: `${app.forum.attribute('apiUrl')}/dialog-messages/${message.id()}/like`,
    })
    .then(
      (payload: any) => {
        app.store.pushPayload(payload);
        m.redraw();
      },
      (error: unknown) => {
        message.pushData({ attributes: { likesCount: before.likesCount }, relationships: { likes: before.likes } } as any);
        m.redraw();

        throw error;
      }
    )
    .finally(() => pending.delete(message));
}

export function likedBy(message: DialogMessage): Mithril.Vnode<any, any> | null {
  const likes = (message.likes() || []).filter(Boolean) as User[];

  if (!likes.length) return null;

  const me = app.session.user;
  const total = Math.max(message.likesCount() ?? likes.length, likes.length);
  const overLimit = total > likes.length;
  const listed = [...likes].sort((a) => (a === me ? -1 : 1)).slice(0, overLimit ? likes.length - 1 : likes.length);

  const names: Mithril.Children[] = listed.map((user) => (
    <Link href={app.route.user(user)}>{user === me ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)}</Link>
  ));

  if (overLimit) {
    names.push(<span>{app.translator.trans('flarum-likes.forum.post.others_link', { count: total - listed.length })}</span>);
  }

  return (
    <div className="Post-likedBy">
      <Icon name="far fa-thumbs-up" />
      {app.translator.trans(`flarum-likes.forum.post.liked_by${listed[0] === me ? '_self' : ''}_text`, {
        count: names.length,
        users: punctuateSeries(names),
      })}
    </div>
  );
}
