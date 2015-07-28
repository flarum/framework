import Notification from 'flarum/components/Notification';

export default class DiscussionStickiedNotification extends Notification {
  icon() {
    return 'thumb-tack';
  }

  href() {
    const notification = this.props.notification;

    return app.route.discussion(notification.subject(), notification.content().postNumber);
  }

  content() {
    return app.trans('sticky.discussion_stickied_notification', {user: this.props.notification.sender()});
  }
}
