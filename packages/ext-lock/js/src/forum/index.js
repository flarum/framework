import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';

import DiscussionLockedPost from './components/DiscussionLockedPost';
import DiscussionLockedNotification from './components/DiscussionLockedNotification';
import addLockBadge from './addLockBadge';
import addLockControl from './addLockControl';

app.initializers.add('flarum-lock', () => {
  app.postComponents.discussionLocked = DiscussionLockedPost;
  app.notificationComponents.discussionLocked = DiscussionLockedNotification;

  Discussion.prototype.isLocked = Model.attribute('isLocked');
  Discussion.prototype.canLock = Model.attribute('canLock');

  addLockBadge();
  addLockControl();

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('discussionLocked', {
      name: 'discussionLocked',
      icon: 'fas fa-lock',
      label: app.translator.trans('flarum-lock.forum.settings.notify_discussion_locked_label'),
    });
  });
});
