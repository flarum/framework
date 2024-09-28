import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';

import DiscussionLockedNotification from './components/DiscussionLockedNotification';
import addLockBadge from './addLockBadge';
import addLockControl from './addLockControl';

export { default as extend } from './extend';

app.initializers.add('flarum-lock', () => {
  app.notificationComponents.discussionLocked = DiscussionLockedNotification;

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
