import type Mithril from 'mithril';
import User from '../models/User';
import Icon from '../components/Icon';

/**
 * The `useronline` helper displays a green circle if the user is online.
 */
export default function userOnline(user: User): Mithril.Vnode<{}, {}> | null {
  if (user.lastSeenAt() && user.isOnline()) {
    return (
      <span className="UserOnline">
        <Icon name={'fas fa-circle'} />
      </span>
    );
  }

  return null;
}
