import IndexPage from 'flarum/components/IndexPage';
import DiscussionPage from 'flarum/components/DiscussionPage';
import ActivityPage from 'flarum/components/ActivityPage';
import SettingsPage from 'flarum/components/SettingsPage';
import NotificationsPage from 'flarum/components/NotificationsPage';

/**
 * The `routes` initializer defines the forum app's routes.
 *
 * @param {App} app
 */
export default function(app) {
  app.routes = {
    'index': { path: '/', component: IndexPage.component() },
    'index.filter': { path: '/:filter', component: IndexPage.component() },

    'discussion.id': { path: '/d/:id', component: DiscussionPage.component() },
    'discussion': { path: '/d/:id/:slug', component: DiscussionPage.component() },
    'discussion.near': { path: '/d/:id/:slug/:near', component: DiscussionPage.component() },

    'user': { path: '/u/:username', component: ActivityPage.component() },
    'user.activity': { path: '/u/:username', component: ActivityPage.component() },
    'user.discussions': { path: '/u/:username/discussions', component: ActivityPage.component({filter: 'startedDiscussion'}) },
    'user.posts': { path: '/u/:username/posts', component: ActivityPage.component({filter: 'posted'}) },

    'settings': { path: '/settings', component: SettingsPage.component() },
    'notifications': { path: '/notifications', component: NotificationsPage.component() }
  };

  /**
   * Generate a URL to a discussion.
   *
   * @param {Discussion} discussion
   * @param {Integer} [near]
   * @return {String}
   */
  app.route.discussion = (discussion, near) => {
    return app.route(near ? 'discussion.near' : 'discussion', {
      id: discussion.id(),
      slug: discussion.slug(),
      near: near
    });
  };

  /**
   * Generate a URL to a post.
   *
   * @param {Post} post
   * @return {String}
   */
  app.route.post = post => {
    return app.route('discussion.near', {
      id: post.discussion().id(),
      slug: post.discussion().slug(),
      near: post.number()
    });
  };

  /**
   * Generate a URL to a user.
   *
   * @param {User} user
   * @return {String}
   */
  app.route.user = user => {
    return app.route('user', {
      username: user.username()
    });
  };
}
