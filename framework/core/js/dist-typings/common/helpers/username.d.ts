import type Mithril from 'mithril';
import User from '../models/User';
/**
 * The `username` helper displays a user's username in a <span className="username">
 * tag. If the user doesn't exist, the username will be displayed as [deleted].
 */
export default function username(user: User | null | undefined | false, transformer?: (name: string) => Mithril.Children): Mithril.Vnode;
