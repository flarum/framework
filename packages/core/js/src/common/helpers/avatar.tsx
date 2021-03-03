import * as Mithril from 'mithril';
import User from '../models/User';

/**
 * The `avatar` helper displays a user's avatar.
 *
 * @param user
 * @param attrs Attributes to apply to the avatar element
 */
export default function avatar(user: User, attrs: Object = {}): Mithril.Vnode {
  attrs.className = 'Avatar ' + (attrs.className || '');
  let content: string = '';

  // If the `title` attribute is set to null or false, we don't want to give the
  // avatar a title. On the other hand, if it hasn't been given at all, we can
  // safely default it to the user's username.
  const hasTitle: boolean | string = attrs.title === 'undefined' || attrs.title;
  if (!hasTitle) delete attrs.title;

  // If a user has been passed, then we will set up an avatar using their
  // uploaded image, or the first letter of their username if they haven't
  // uploaded one.
  if (user) {
    const username: string = user.displayName() || '?';
    const avatarUrl: string = user.avatarUrl();

    if (hasTitle) attrs.title = attrs.title || username;

    if (avatarUrl) {
      return <img {...attrs} src={avatarUrl} alt="" />;
    }

    content = username.charAt(0).toUpperCase();
    attrs.style = { background: user.color() };
  }

  return <span {...attrs}>{content}</span>;
}
