import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

export default class NewPostNotification extends Notification {
  icon() {
    return 'fas fa-star';
  }

  href() {
    const notification = this.attrs.notification;
    const discussion = notification.subject();
    const content = notification.content() || {};

    return app.route.discussion(discussion, content.postNumber);
  }

  content() {
    return app.translator.trans('flarum-subscriptions.forum.notifications.new_post_text', { user: this.attrs.notification.fromUser() });
  }
}
