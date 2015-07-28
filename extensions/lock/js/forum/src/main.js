import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import NotificationGrid from 'flarum/components/NotificationGrid';

import DiscussionLockedPost from 'lock/components/DiscussionLockedPost';
import DiscussionLockedNotification from 'lock/components/DiscussionLockedNotification';
import addLockBadge from 'lock/addLockBadge';
import addLockControl from 'lock/addLockControl';

app.postComponents.discussionLocked = DiscussionLockedPost;
app.notificationComponents.discussionLocked = DiscussionLockedNotification;

Discussion.prototype.isLocked = Model.attribute('isLocked');
Discussion.prototype.canLock = Model.attribute('canLock');

addLockBadge();
addLockControl();

extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
  items.add('discussionLocked', {
    name: 'discussionLocked',
    icon: 'lock',
    label: app.trans('lock.notify_discussion_locked')
  });
});
