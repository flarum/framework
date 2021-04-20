import DiscussionPageResolver from './resolvers/DiscussionPageResolver';
import DiscussionPage from './components/DiscussionPage';
import NotificationsPage from './components/NotificationsPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function (app) {
  const IndexPage = () => import(/* webpackChunkName: "forum/components/IndexPage" */ './components/IndexPage');
  const PostsUserPage = () => import(/* webpackChunkName: "forum/components/PostsUserPage" */ './components/PostsUserPage');
  const DiscussionsUserPage = () => import(/* webpackChunkName: "forum/components/DiscussionsUserPage" */ './components/DiscussionsUserPage');
  const SettingsPage = () => import(/* webpackChunkName: "forum/components/SettingsPage" */ './components/SettingsPage');

  app.routes = {
    index: { path: '/all', component: IndexPage },

    discussion: { path: '/d/:id', component: DiscussionPage, resolverClass: DiscussionPageResolver },
    'discussion.near': { path: '/d/:id/:near', component: DiscussionPage, resolverClass: DiscussionPageResolver },

    user: { path: '/u/:username', component: PostsUserPage },
    'user.posts': { path: '/u/:username', component: PostsUserPage },
    'user.discussions': { path: '/u/:username/discussions', component: DiscussionsUserPage },

    settings: { path: '/settings', component: SettingsPage },
    notifications: { path: '/notifications', component: NotificationsPage },
  };

  /**
   * Generate a URL to a discussion.
   *
   * @param {Discussion} discussion
   * @param {Integer} [near]
   * @return {String}
   */
  app.route.discussion = (discussion, near) => {
    return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
      id: discussion.slug(),
      near: near && near !== 1 ? near : undefined,
    });
  };

  /**
   * Generate a URL to a post.
   *
   * @param {Post} post
   * @return {String}
   */
  app.route.post = (post) => {
    return app.route.discussion(post.discussion(), post.number());
  };

  /**
   * Generate a URL to a user.
   *
   * @param {User} user
   * @return {String}
   */
  app.route.user = (user) => {
    return app.route('user', {
      username: user.slug(),
    });
  };
}
