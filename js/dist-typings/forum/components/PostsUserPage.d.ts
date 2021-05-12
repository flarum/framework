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
    posts: any[] | undefined;
    /**
     * The number of activity items to load per request.
     *
     * @type {Integer}
     */
    loadLimit: any;
    /**
     * Clear and reload the user's activity feed.
     *
     * @public
     */
    public refresh(): void;
    /**
     * Load a new page of the user's activity feed.
     *
     * @param {Integer} [offset] The position to start getting results from.
     * @return {Promise}
     * @protected
     */
    protected loadResults(offset?: any): Promise<any>;
    /**
     * Load the next page of results.
     *
     * @public
     */
    public loadMore(): void;
    /**
     * Parse results and append them to the activity feed.
     *
     * @param {Post[]} results
     * @return {Post[]}
     */
    parseResults(results: any[]): any[];
}
import UserPage from "./UserPage";
