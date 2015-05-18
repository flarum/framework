import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class DiscussionRenamedNotification extends Notification {
  view() {
    var notification = this.props.notification;

    return super.view({
      href: app.route.discussion(notification.subject(), notification.content().postNumber),
      icon: 'pencil',
      content: [username(notification.sender()), ' renamed']
    });
  }
}
