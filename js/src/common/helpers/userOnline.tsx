import icon from './icon';
import User from '../models/User';

/**
 * The `useronline` helper displays a green circle if the user is online
 *
 * @param {User} user
 * @return {Object}
 */
export default function userOnline(user: User) {
    if (user.lastSeenAt() && user.isOnline()) {
        return <span className="UserOnline">{icon('fas fa-circle')}</span>;
    }
}
