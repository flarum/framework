import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class NotificationUserMentioned extends Notification {
  view() {
    var notification = this.props.notification;
    var post = notification.subject();

    return super.view({
      href: app.route.discussion(post.discussion(), post.number()),
      icon: 'at',
      content: [username(notification.sender()), ' mentioned you']
    });
  }
}
