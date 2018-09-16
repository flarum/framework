import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/humanTime';

export default class UserSuspendedNotification extends Notification {
  icon() {
    return 'ban';
  }

  href() {
    return app.route.user(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;
    const suspendedUntil = notification.content();
    const timeReadable = moment(suspendedUntil.date).from(notification.createdAt(), true);

    return app.translator.trans('flarum-suspend.forum.notifications.user_suspended_text', {
      user: notification.fromUser(),
      timeReadable,
    });
  }
}
