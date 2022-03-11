import app from 'flarum/forum/app';
import Notification from 'flarum/components/Notification';

export default class UserUnsuspendedNotification extends Notification {
  icon() {
    return 'fas fa-ban';
  }

  href() {
    return app.route.user(this.attrs.notification.subject());
  }

  content() {
    const notification = this.attrs.notification;

    return app.translator.trans('flarum-suspend.forum.notifications.user_unsuspended_text');
  }
}
