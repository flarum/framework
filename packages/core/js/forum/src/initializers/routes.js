import IndexPage from 'flarum/components/index-page';
import DiscussionPage from 'flarum/components/discussion-page';
import ActivityPage from 'flarum/components/activity-page';
import SettingsPage from 'flarum/components/settings-page';

export default function(app) {
  app.routes = {
    'index':            ['/', IndexPage.component()],
    'index.filter':     ['/:filter', IndexPage.component()],

    'discussion':       ['/d/:id/:slug', DiscussionPage.component()],
    'discussion.near':  ['/d/:id/:slug/:near', DiscussionPage.component()],

    'user':             ['/u/:username', ActivityPage.component()],
    'user.activity':    ['/u/:username', ActivityPage.component()],
    'user.discussions': ['/u/:username/discussions', ActivityPage.component({filter: 'discussion'})],
    'user.posts':       ['/u/:username/posts', ActivityPage.component({filter: 'post'})],

    'settings':         ['/settings', SettingsPage.component()]
  };
}
