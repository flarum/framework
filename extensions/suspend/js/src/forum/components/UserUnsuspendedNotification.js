import Notification from 'flarum/components/Notification';

export default class UserUnsuspendedNotification extends Notification {
  icon() {
    return 'fas fa-ban';
  }

  href() {
    return app.route.user(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;

    return app.translator.trans('flarum-suspend.forum.notifications.user_unsuspended_text', {
      user: notification.fromUser(),
    });
  }
}
