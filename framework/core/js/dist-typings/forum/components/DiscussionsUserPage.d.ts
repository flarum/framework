import UserPage, { IUserPageAttrs } from './UserPage';
import DiscussionListState from '../states/DiscussionListState';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
/**
 * The `DiscussionsUserPage` component shows a discussion list inside of a user
 * page.
 */
export default class DiscussionsUserPage extends UserPage<IUserPageAttrs, DiscussionListState> {
    oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>): void;
    show(user: User): void;
    content(): JSX.Element;
}
