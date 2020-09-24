import Notification from 'flarum/components/Notification';

export default class UserSuspendedNotification extends Notification {
  icon() {
    return 'fas fa-ban';
  }

  href() {
    return app.route.user(this.attrs.notification.subject());
  }

  content() {
    const notification = this.attrs.notification;
    const suspendedUntil = notification.content();
    const timeReadable = dayjs(suspendedUntil.date).from(notification.createdAt(), true);

    return app.translator.trans('flarum-suspend.forum.notifications.user_suspended_text', {
      user: notification.fromUser(),
      timeReadable,
    });
  }
}
