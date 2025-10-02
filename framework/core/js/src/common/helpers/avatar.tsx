import type Mithril from 'mithril';
import type { ComponentAttrs } from '../Component';
import User from '../models/User';
import classList from '../utils/classList';

export interface AvatarAttrs extends ComponentAttrs {}

/**
 * The `avatar` helper displays a user's avatar.
 *
 * @param user
 * @param attrs Attributes to apply to the avatar element
 */
export default function avatar(user: User | null, attrs: ComponentAttrs = {}): Mithril.Vnode {
  attrs.className = classList('Avatar', attrs.className);
  attrs.loading ??= 'lazy';
  let content: string = '';

  // If the `title` attribute is set to null or false, we don't want to give the
  // avatar a title. On the other hand, if it hasn't been given at all, we can
  // safely default it to the user's username.
  const hasTitle: boolean | string = attrs.title === 'undefined' || attrs.title;
  if (!hasTitle) delete attrs.title;

  // If the `alt` attribute is set to null or false, we don't want to give the
  // avatar an alt description. If it hasn't been set, we can default it later
  // to the user's display name for accessibility.
  const hasAlt: boolean | string = attrs.alt === 'undefined' || attrs.alt;
  if (!hasAlt) delete attrs.alt;

  // If a user has been passed, then we will set up an avatar using their
  // uploaded image, or the first letter of their username if they haven't
  // uploaded one.
  if (user) {
    const username = user.displayName() || '?';
    const avatarUrl = user.avatarUrl();

    if (hasTitle) attrs.title = attrs.title || username;

    // If alt has not been explicitly set, default it to the username
    // so screen readers have meaningful context.
    if (attrs.alt === undefined) {
      attrs.alt = username;
    }

    if (avatarUrl) {
      return <img {...attrs} src={avatarUrl} />;
    }

    content = username.charAt(0).toUpperCase();
    attrs.style = { '--avatar-bg': user.color() };
  } else {
    // If there is no user, and no alt provided, set alt to an empty string
    // so the avatar is treated as decorative.
    if (attrs.alt === undefined) {
      attrs.alt = '';
    }
  }

  return <span {...attrs}>{content}</span>;
}
