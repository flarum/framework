import * as Mithril from 'mithril';
import User from '../models/User';
import icon from './icon';

/**
 * The `useronline` helper displays a green circle if the user is online
 */
export default function userOnline(user: User): Mithril.Vnode {
  if (user.lastSeenAt() && user.isOnline()) {
    return <span className="UserOnline">{icon('fas fa-circle')}</span>;
  }
}
