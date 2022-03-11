import ForumApplication from './ForumApplication';
import Discussion from '../common/models/Discussion';
import Post from '../common/models/Post';
import User from '../common/models/User';
/**
 * The `routes` initializer defines the forum app's routes.
 */
export default function (app: ForumApplication): void;
export declare function makeRouteHelpers(app: ForumApplication): {
    /**
     * Generate a URL to a discussion.
     */
    discussion: (discussion: Discussion, near?: number | undefined) => string;
    /**
     * Generate a URL to a post.
     */
    post: (post: Post) => string;
    /**
     * Generate a URL to a user.
     */
    user: (user: User) => string;
};
