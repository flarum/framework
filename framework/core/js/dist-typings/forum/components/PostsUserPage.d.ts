/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
    /**
     * Whether or not the activity feed is currently loading.
     *
     * @type {Boolean}
     */
    loading: boolean | undefined;
    /**
     * Whether or not there are any more activity items that can be loaded.
     *
     * @type {Boolean}
     */
    moreResults: boolean | undefined;
    /**
     * The Post models in the feed.
     *
     * @type {Post[]}
     */
    posts: Post[] | undefined;
    /**
     * The number of activity items to load per request.
     *
     * @type {number}
     */
    loadLimit: number | undefined;
    /**
     * Clear and reload the user's activity feed.
     */
    refresh(): void;
    /**
     * Load a new page of the user's activity feed.
     *
     * @param {number} [offset] The position to start getting results from.
     * @return {Promise<import('../../common/models/Post').default[]>}
     * @protected
     */
    protected loadResults(offset?: number | undefined): Promise<import('../../common/models/Post').default[]>;
    /**
     * Load the next page of results.
     */
    loadMore(): void;
    /**
     * Parse results and append them to the activity feed.
     *
     * @param {import('../../common/models/Post').default[]} results
     * @return {import('../../common/models/Post').default[]}
     */
    parseResults(results: import('../../common/models/Post').default[]): import('../../common/models/Post').default[];
}
import UserPage from "./UserPage";
