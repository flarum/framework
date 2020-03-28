import UserPage from './UserPage';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';
import Placeholder from '../../common/components/Placeholder';
import CommentPost from './CommentPost';
import Post from '../../common/models/Post';

/**
 * The `PostsUserPage` component shows a user's activity feed inside of their
 * profile.
 */
export default class PostsUserPage extends UserPage {
    /**
     * Whether or not the activity feed is currently loading.
     */
    loading = true;

    /**
     * Whether or not there are any more activity items that can be loaded.
     */
    moreResults = false;

    /**
     * The Post models in the feed.
     */
    posts: Post[] = [];

    /**
     * The number of activity items to load per request.
     */
    loadLimit = 20;

    oninit(vnode) {
        super.oninit(vnode);

        this.loadUser(vnode.attrs.username);
    }

    onupdate(vnode) {
        super.onupdate(vnode);

        this.loadUser(vnode.attrs.username);
    }

    content() {
        if (this.posts.length === 0 && !this.loading) {
            return (
                <div className="PostsUserPage">
                    <Placeholder text={app.translator.trans('core.forum.user.posts_empty_text')} />
                </div>
            );
        }

        let footer;

        if (this.loading) {
            footer = LoadingIndicator.component();
        } else if (this.moreResults) {
            footer = (
                <div className="PostsUserPage-loadMore">
                    {Button.component({
                        children: app.translator.trans('core.forum.user.posts_load_more_button'),
                        className: 'Button',
                        onclick: this.loadMore.bind(this),
                    })}
                </div>
            );
        }

        return (
            <div className="PostsUserPage">
                <ul className="PostsUserPage-list">
                    {this.posts.map((post) => (
                        <li>
                            <div className="PostsUserPage-discussion">
                                {app.translator.trans('core.forum.user.in_discussion_text', {
                                    discussion: <m.route.Link href={app.route.post(post)}>{post.discussion().title()}</m.route.Link>,
                                })}
                            </div>
                            {CommentPost.component({ post })}
                        </li>
                    ))}
                </ul>
                <div className="PostsUserPage-loadMore">{footer}</div>
            </div>
        );
    }

    /**
     * Initialize the component with a user, and trigger the loading of their
     * activity feed.
     */
    show(user) {
        super.show(user);

        this.refresh();
    }

    /**
     * Clear and reload the user's activity feed.
     */
    refresh() {
        this.loading = true;
        this.posts = [];

        m.redraw();

        this.loadResults().then(this.parseResults.bind(this));
    }

    /**
     * Load a new page of the user's activity feed.
     *
     * @param offset The position to start getting results from.
     */
    protected loadResults(offset?: number): Promise<Post[]> {
        return app.store.find<Post>('posts', {
            filter: {
                user: this.user.id(),
                type: 'comment',
            },
            page: { offset, limit: this.loadLimit },
            sort: '-createdAt',
        });
    }

    /**
     * Load the next page of results.
     */
    loadMore() {
        this.loading = true;
        this.loadResults(this.posts.length).then(this.parseResults.bind(this));
    }

    /**
     * Parse results and append them to the activity feed.
     */
    parseResults(results: Post[]): Post[] {
        this.loading = false;

        [].push.apply(this.posts, results);

        this.moreResults = results.length >= this.loadLimit;
        m.redraw();

        return results;
    }
}
