import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class NewPostNotification extends Notification {
  view() {
    var notification = this.props.notification;
    var discussion = notification.subject();
    var content = notification.content() || {};

    return super.view({
      href: app.route.discussion(discussion, content.postNumber),
      icon: 'star',
      content: [username(notification.sender()), ' posted']
    });
  }
}
