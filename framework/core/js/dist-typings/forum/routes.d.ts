import ForumApplication from './ForumApplication';
import Discussion from '../common/models/Discussion';
import type Post from '../common/models/Post';
import type User from '../common/models/User';
/**
 * Helper functions to generate URLs to form pages.
 */
export interface ForumRoutes {
    discussion: (discussion: Discussion, near?: number) => string;
    post: (post: Post) => string;
    user: (user: User) => string;
}
/**
 * The `routes` initializer defines the forum app's routes.
 */
export default function (app: ForumApplication): void;
export declare function makeRouteHelpers(app: ForumApplication): {
    /**
     * Generate a URL to a discussion.
     */
    discussion: (discussion: Discussion, near?: number) => string;
    /**
     * Generate a URL to a post.
     */
    post: (post: Post) => string;
    /**
     * Generate a URL to a user.
     */
    user: (user: User) => string;
};
