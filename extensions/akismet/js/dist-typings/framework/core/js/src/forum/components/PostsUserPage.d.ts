import UserPage, { IUserPageAttrs } from './UserPage';
import type Post from '../../common/models/Post';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
    /**
     * Whether or not the activity feed is currently loading.
     */
    loading: boolean;
    /**
     * Whether or not there are any more activity items that can be loaded.
     */
    moreResults: boolean;
    /**
     * The Post models in the feed.
     */
    posts: Post[];
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
    /**
     * Clear and reload the user's activity feed.
     */
    refresh(): void;
    /**
     * Load a new page of the user's activity feed.
     *
     * @protected
     */
    loadResults(offset?: number): Promise<import("../../common/Store").ApiResponsePlural<Post>>;
    /**
     * Load the next page of results.
     */
    loadMore(): void;
    /**
     * Parse results and append them to the activity feed.
     */
    parseResults(results: Post[]): Post[];
}
