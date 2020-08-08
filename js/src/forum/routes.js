import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import SettingsPage from './components/SettingsPage';
import NotificationsPage from './components/NotificationsPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function (app) {
  app.routes = {
    index: { path: '/all', component: IndexPage },
    'index.filter': { path: '/:filter', component: IndexPage },

    discussion: { path: '/d/:id', component: DiscussionPage },
    'discussion.near': { path: '/d/:id/:near', component: DiscussionPage },

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
    const slug = discussion.slug();
    return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
      id: discussion.id() + (slug.trim() ? '-' + slug : ''),
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
      username: user.username(),
    });
  };
}
