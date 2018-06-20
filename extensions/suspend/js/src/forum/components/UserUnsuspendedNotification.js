import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/humanTime';

export default class UserUnsuspendedNotification extends Notification {
  icon() {
    return 'ban';
  }

  href() {
    return app.route.user(this.props.notification.subject());
  }

  content() {
    const notification = this.props.notification;
    const actor = notification.sender();

    return app.translator.transChoice('flarum-suspend.forum.notifications.user_unsuspended_text', {
      actor,
    });
  }
}
