import { extend, override } from 'flarum/extension-utils';
import app from 'flarum/app';
import Model from 'flarum/model';
import Component from 'flarum/component';
import Discussion from 'flarum/models/discussion';
import Badge from 'flarum/components/badge';
import ActionButton from 'flarum/components/action-button';
import SettingsPage from 'flarum/components/settings-page';
import DiscussionPage from 'flarum/components/discussion-page';
import IndexPage from 'flarum/components/index-page';
import IndexNavItem from 'flarum/components/index-nav-item';
import DiscussionList from 'flarum/components/discussion-list';
import icon from 'flarum/helpers/icon';

import SubscriptionMenu from 'flarum-subscriptions/components/subscription-menu';
import NewPostNotification from 'flarum-subscriptions/components/new-post-notification';

app.initializers.add('flarum-subscriptions', function() {

  app.notificationComponentRegistry['newPost'] = NewPostNotification;

  Discussion.prototype.subscription = Model.prop('subscription');

  // Add subscription badges to discussions.
  extend(Discussion.prototype, 'badges', function(badges) {
    var badge;

    switch (this.subscription()) {
      case 'follow':
        badge = Badge.component({ label: 'Following', icon: 'star', className: 'badge-follow' });
        break;

      case 'ignore':
        badge = Badge.component({ label: 'Ignoring', icon: 'eye-slash', className: 'badge-ignore' });
    }

    if (badge) {
      badges.add('subscription', badge);
    }
  });

  extend(Discussion.prototype, 'userControls', function(items, context) {
    if (app.session.user() && !(context instanceof DiscussionPage)) {
      var states = {
        none: {label: 'Follow', icon: 'star', save: 'follow'},
        follow: {label: 'Unfollow', icon: 'star-o', save: false},
        ignore: {label: 'Unignore', icon: 'eye', save: false}
      };
      var subscription = this.subscription() || 'none';

      items.add('subscription', ActionButton.component({
        label: states[subscription].label,
        icon: states[subscription].icon,
        onclick: this.save.bind(this, {subscription: states[subscription].save})
      }));
    }
  });

  extend(DiscussionPage.prototype, 'sidebarItems', function(items) {
    if (app.session.user()) {
      var discussion = this.discussion();
      items.add('subscription', SubscriptionMenu.component({discussion}), {after: 'controls'});
    }
  });

  extend(IndexPage.prototype, 'navItems', function(items) {
    if (app.session.user()) {
      var params = this.stickyParams();
      params.filter = 'following';

      items.add('following', IndexNavItem.component({
        href: app.route('index.filter', params),
        label: 'Following',
        icon: 'star'
      }), {after: 'allDiscussions'});
    }
  });

  extend(DiscussionList.prototype, 'params', function(params) {
    if (params.filter === 'following') {
      params.q = (params.q || '')+' is:following';
    }
  });

  // Add a notification preference.
  extend(SettingsPage.prototype, 'notificationTypes', function(items) {
    items.add('newPost', {
      name: 'newPost',
      label: [icon('star'), " Someone posts in a discussion I'm following"]
    });
  });

});
