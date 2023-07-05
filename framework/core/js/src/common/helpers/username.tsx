import app from '../../common/app';
import type Mithril from 'mithril';
import User from '../models/User';
import extractText from '../utils/extractText';

/**
 * The `username` helper displays a user's username in a <span className="username">
 * tag. If the user doesn't exist, the username will be displayed as [deleted].
 */
export default function username(user: User | null | undefined | false, transformer?: (name: string) => Mithril.Children): Mithril.Vnode {
  const name = (user && user.displayName()) || extractText(app.translator.trans('core.lib.username.deleted_text'));
  const children = transformer ? transformer(name) : name;

  return <span className="username">{children}</span>;
}
