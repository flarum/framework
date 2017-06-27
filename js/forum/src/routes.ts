import IndexPage from './components/IndexPage';
import DiscussionPage from './components/DiscussionPage';
import PostsUserPage from './components/PostsUserPage';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import SettingsPage from './components/SettingsPage';
import NotificationsPage from './components/NotificationsPage';

export default function(router) {
  router.add('index', '/all', IndexPage);
  router.add('index.filter', '/:filter', IndexPage);

  router.add('discussion', '/d/:id', DiscussionPage);
  router.add('discussion.near', '/d/:id/:near', DiscussionPage);

  router.add('user', '/u/:username', PostsUserPage);
  router.add('user.posts', '/u/:username', PostsUserPage);
  router.add('user.discussions', '/u/:username/discussions', DiscussionsUserPage);

  router.add('settings', '/settings', SettingsPage);
  router.add('notifications', '/notifications', NotificationsPage);

  // TODO: work out where to put these shortcut functions
  // /**
  //  * Generate a URL to a discussion.
  //  *
  //  * @param {Discussion} discussion
  //  * @param {Integer} [near]
  //  * @return {String}
  //  */
  // app.route.discussion = (discussion, near) => {
  //   return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
  //     id: discussion.id() + '-' + discussion.slug(),
  //     near: near && near !== 1 ? near : undefined
  //   });
  // };

  // /**
  //  * Generate a URL to a post.
  //  *
  //  * @param {Post} post
  //  * @return {String}
  //  */
  // app.route.post = post => {
  //   return app.route.discussion(post.discussion(), post.number());
  // };

  // /**
  //  * Generate a URL to a user.
  //  *
  //  * @param {User} user
  //  * @return {String}
  //  */
  // app.route.user = user => {
  //   return app.route('user', {
  //     username: user.username()
  //   });
  // };
}
