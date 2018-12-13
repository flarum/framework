import icon from './icon';

/**
 * The `useronline` helper displays a green circle if the user is online
 *
 * @param {User} user
 * @return {Object}
 */
export default function userOnline(user) {
    if (user.lastSeenAt() && user.isOnline()) {
        return <span className="UserOnline">{icon('fas fa-circle')}</span>;
    }
}
