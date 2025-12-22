import type User from '../models/User';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import Component from '../Component';

export interface IAvatarAttrs extends ComponentAttrs {
  user: User | null;
}

export default class Avatar<CustomAttrs extends IAvatarAttrs = IAvatarAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { user, ...attrs } = vnode.attrs as IAvatarAttrs;

    attrs.className = classList('Avatar', attrs.className);
    attrs.loading ??= 'lazy';
    let content: string = '';

    // If the `title` attribute is set to null or false, we don't want to give the
    // avatar a title. On the other hand, if it hasn't been given at all, we can
    // safely default it to the user's username.
    const hasTitle: boolean | string = (attrs as any).title === 'undefined' || (attrs as any).title;
    if (!hasTitle) delete (attrs as any).title;

    // If a user has been passed, then we will set up an avatar using their
    // uploaded image, or the first letter of their username if they haven't
    // uploaded one.
    if (user) {
      const username = user.displayName() || '?';
      const avatarUrl = user.avatarUrl();

      if (hasTitle) (attrs as any).title = (attrs as any).title || username;

      // Alt text logic:
      // If the `alt` attribute is set to null or false, we don't want to give the
      // avatar an alt description. If it hasn't been provided, we'll default it to
      // the user's display name *when rendering an <img>* so screen readers have context.
      const hasAlt: boolean | string = (attrs as any).alt === 'undefined' || (attrs as any).alt;
      if (!hasAlt) delete (attrs as any).alt;

      if (avatarUrl) {
        // Default alt to username unless explicitly overridden.
        if ((attrs as any).alt === undefined) {
          (attrs as any).alt = username;
        }

        return <img {...attrs} src={avatarUrl} />;
      }

      content = username.charAt(0).toUpperCase();
      attrs.style = !window.testing && { '--avatar-bg': user.color() };

      delete attrs.loading;
      attrs.role = 'img';
      attrs['aria-label'] = username;
    }

    // Note: We intentionally do NOT set `alt` when rendering the fallback <span>,
    // as `alt` is only valid for certain elements (e.g., <img>, <area>, <input type="image">).
    return <span {...attrs}>{content}</span>;
  }
}
