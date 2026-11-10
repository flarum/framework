import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import addLockBadge from './addLockBadge';
import addLockControl from './addLockControl';
import extendRealtime from './extendRealtime';

export { default as extend } from './extend';

app.initializers.add('flarum-lock', () => {
  addLockBadge();
  addLockControl();

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('discussionLocked', {
      name: 'discussionLocked',
      icon: 'fas fa-lock',
      label: app.translator.trans('flarum-lock.forum.settings.notify_discussion_locked_label'),
    });
  });

  // Register a discussion stream update event with flarum/realtime when enabled.
  // Locking or unlocking a discussion will trigger a DiscussionPage stream reload for other users.
  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});
