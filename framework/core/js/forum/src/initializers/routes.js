import IndexPage from 'flarum/components/index-page';
import DiscussionPage from 'flarum/components/discussion-page';
import ActivityPage from 'flarum/components/activity-page';
import SettingsPage from 'flarum/components/settings-page';
import NotificationsPage from 'flarum/components/notifications-page';

export default function(app) {
  app.routes = {
    'index':            ['/', IndexPage.component()],
    'index.filter':     ['/:filter', IndexPage.component()],

    'discussion':       ['/d/:id/:slug', DiscussionPage.component()],
    'discussion.near':  ['/d/:id/:slug/:near', DiscussionPage.component()],

    'user':             ['/u/:username', ActivityPage.component()],
    'user.activity':    ['/u/:username', ActivityPage.component()],
    'user.discussions': ['/u/:username/discussions', ActivityPage.component({filter: 'startedDiscussion'})],
    'user.posts':       ['/u/:username/posts', ActivityPage.component({filter: 'posted'})],

    'settings':         ['/settings', SettingsPage.component()],
    'notifications':    ['/notifications', NotificationsPage.component()]
  };

  app.route.discussion = function(discussion, near) {
    return app.route(near ? 'discussion.near' : 'discussion', {
      id: discussion.id(),
      slug: discussion.slug(),
      near: near
    });
  };

  app.route.post = function(post) {
    return app.route('discussion.near', {
      id: post.discussion().id(),
      slug: post.discussion().slug(),
      near: post.number()
    });
  };

  app.route.user = function(user) {
    return app.route('user', {
      username: user.username()
    });
  };
}
