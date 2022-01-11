import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class DiscussionLockedNotification extends Notification {
  icon() {
    return 'fas fa-lock';
  }

  href() {
    const notification = this.attrs.notification;

    return app.route.discussion(notification.subject(), notification.content().postNumber);
  }

  content() {
    return app.translator.trans('flarum-lock.forum.notifications.discussion_locked_text', { user: this.attrs.notification.fromUser() });
  }
}
