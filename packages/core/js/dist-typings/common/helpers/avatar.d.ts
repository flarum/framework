import type Mithril from 'mithril';
import type { ComponentAttrs } from '../Component';
import User from '../models/User';
export interface AvatarAttrs extends ComponentAttrs {
}
/**
 * The `avatar` helper displays a user's avatar.
 *
 * @param user
 * @param attrs Attributes to apply to the avatar element
 */
export default function avatar(user: User, attrs?: ComponentAttrs): Mithril.Vnode;
