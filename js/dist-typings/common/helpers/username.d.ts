import * as Mithril from 'mithril';
import User from '../models/User';
/**
 * The `username` helper displays a user's username in a <span class="username">
 * tag. If the user doesn't exist, the username will be displayed as [deleted].
 */
export default function username(user: User): Mithril.Vnode;
