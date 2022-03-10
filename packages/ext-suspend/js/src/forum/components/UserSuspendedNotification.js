import app from 'flarum/forum/app';
import Notification from 'flarum/components/Notification';
import { isPermanentSuspensionDate } from '../helpers/suspensionHelper';

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
    const timeReadable = dayjs(suspendedUntil).from(notification.createdAt(), true);

    return isPermanentSuspensionDate(suspendedUntil)
      ? app.translator.trans('flarum-suspend.forum.notifications.user_suspended_indefinite_text')
      : app.translator.trans('flarum-suspend.forum.notifications.user_suspended_text', {
          timeReadable,
        });
  }
}
