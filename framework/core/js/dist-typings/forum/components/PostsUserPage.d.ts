import UserPage, { IUserPageAttrs } from './UserPage';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
import PostListState from '../states/PostListState';
/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
    /**
     * The state of the Post models in the feed.
     */
    posts: PostListState;
    /**
     * The number of activity items to load per request.
     */
    loadLimit: number;
    oninit(vnode: Mithril.Vnode<IUserPageAttrs, this>): void;
    content(): JSX.Element;
    /**
     * Initialize the component with a user, and trigger the loading of their
     * activity feed.
     */
    show(user: User): void;
    params(user: User): {
        filter: {
            author: string;
        };
    };
}
