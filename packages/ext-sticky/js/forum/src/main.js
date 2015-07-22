import { extend, notificationType } from 'flarum/extend';
import app from 'flarum/app';
import Model from 'flarum/Model';
import Discussion from 'flarum/models/Discussion';
import NotificationGrid from 'flarum/components/NotificationGrid';

import DiscussionStickiedPost from 'sticky/components/DiscussionStickiedPost';
import DiscussionStickiedNotification from 'sticky/components/DiscussionStickiedNotification';
import addStickyBadge from 'sticky/addStickyBadge';
import addStickyControl from 'sticky/addStickyControl';
import addStickyExcerpt from 'sticky/addStickyExcerpt';

app.postComponents.discussionStickied = DiscussionStickiedPost;
app.notificationComponents.discussionStickied = DiscussionStickiedNotification;

Discussion.prototype.isSticky = Model.attribute('isSticky');
Discussion.prototype.canSticky = Model.attribute('canSticky');

addStickyBadge();
addStickyControl();
addStickyExcerpt();

extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
  items.add('discussionStickied', {
    name: 'discussionStickied',
    icon: 'thumb-tack',
    label: app.trans('sticky.notify_discussion_stickied')
  });
});
