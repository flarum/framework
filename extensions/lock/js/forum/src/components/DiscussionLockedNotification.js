import Notification from 'flarum/components/Notification';

export default class DiscussionLockedNotification extends Notification {
  icon() {
    return 'lock';
  }

  href() {
    const notification = this.props.notification;

    return app.route.discussion(notification.subject(), notification.content().postNumber);
  }

  content() {
    return app.trans('lock.discussion_locked_notification', {user: this.props.notification.sender()});
  }
}
