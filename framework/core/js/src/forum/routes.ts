import ForumApplication from './ForumApplication';
import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionPageResolver from './resolvers/DiscussionPageResolver';
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
export default function (app: ForumApplication) {
  app.routes = {
    index: { path: '/all', component: IndexPage },
    posts: { path: '/posts', component: () => import('./components/PostsPage') },

    discussion: { path: '/d/:id', component: DiscussionPage, resolverClass: DiscussionPageResolver },
    'discussion.near': { path: '/d/:id/:near', component: DiscussionPage, resolverClass: DiscussionPageResolver },

    user: { path: '/u/:username', component: PostsUserPage },
    'user.posts': { path: '/u/:username', component: PostsUserPage },
    'user.discussions': { path: '/u/:username/discussions', component: () => import('./components/DiscussionsUserPage') },

    settings: { path: '/settings', component: () => import('./components/SettingsPage') },
    'user.security': { path: '/u/:username/security', component: () => import('./components/UserSecurityPage') },
    notifications: { path: '/notifications', component: () => import('./components/NotificationsPage') },
  };
}

export function makeRouteHelpers(app: ForumApplication) {
  return {
    /**
     * Generate a URL to a discussion.
     */
    discussion: (discussion: Discussion, near?: number) => {
      return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
        id: discussion.slug(),
        near: near && near !== 1 ? near : undefined,
      });
    },

    /**
     * Generate a URL to a post.
     */
    post: (post: Post) => {
      return app.route.discussion(post.discussion(), post.number());
    },

    /**
     * Generate a URL to a user.
     */
    user: (user: User) => {
      return app.route('user', {
        username: user.slug(),
      });
    },
  };
}
