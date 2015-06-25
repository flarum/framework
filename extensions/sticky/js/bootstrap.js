import { extend } from 'flarum/extension-utils';
import Model from 'flarum/model';
import Discussion from 'flarum/models/discussion';
import DiscussionPage from 'flarum/components/discussion-page';
import DiscussionList from 'flarum/components/discussion-list';
import DiscussionListItem from 'flarum/components/discussion-list-item';
import Badge from 'flarum/components/badge';
import ActionButton from 'flarum/components/action-button';
import SettingsPage from 'flarum/components/settings-page';
import icon from 'flarum/helpers/icon';
import truncate from 'flarum/utils/truncate';
import app from 'flarum/app';

import DiscussionStickiedPost from 'flarum-sticky/components/discussion-stickied-post';
import DiscussionStickiedNotification from 'flarum-sticky/components/discussion-stickied-notification';

app.initializers.add('sticky', function() {

  // Register components.
  app.postComponentRegistry['discussionStickied'] = DiscussionStickiedPost;
  app.notificationComponentRegistry['discussionStickied'] = DiscussionStickiedNotification;

  Discussion.prototype.isSticky = Model.prop('isSticky');
  Discussion.prototype.canSticky = Model.prop('canSticky');

  // Add a sticky badge to discussions.
  extend(Discussion.prototype, 'badges', function(badges) {
    if (this.isSticky()) {
      badges.add('sticky', Badge.component({
        label: 'Sticky',
        icon: 'thumb-tack',
        className: 'badge-sticky',
      }));
    }
  });

  function toggleSticky() {
    this.save({isSticky: !this.isSticky()}).then(discussion => {
      if (app.current instanceof DiscussionPage) {
        app.current.stream.sync();
      }
      m.redraw();
    });
  }

  // Add a sticky control to discussions.
  extend(Discussion.prototype, 'controls', function(items) {
    if (this.canSticky()) {
      items.add('sticky', ActionButton.component({
        label: this.isSticky() ? 'Unsticky' : 'Sticky',
        icon: 'thumb-tack',
        onclick: toggleSticky.bind(this)
      }), {after: 'rename'});
    }
  });

  // Add a notification preference.
  extend(SettingsPage.prototype, 'notificationTypes', function(items) {
    items.add('discussionStickied', {
      name: 'discussionStickied',
      label: [icon('thumb-tack'), ' Someone stickies a discussion I started']
    }, {after: 'discussionRenamed'});
  });

  extend(DiscussionList.prototype, 'params', function(params) {
    params.include.push('startPost');
  });

  extend(DiscussionListItem.prototype, 'infoItems', function(items) {
    var discussion = this.props.discussion;

    if (discussion.isSticky()) {
      var startPost = discussion.startPost();
      if (startPost) {
        var excerpt = m('span', truncate(startPost.contentPlain(), 200));
        excerpt.wrapperClass = 'discussion-excerpt';
        items.add('excerpt', excerpt, {last: true});
      }
    }
  });
});
