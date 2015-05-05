import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class NotificationDiscussionRenamed extends Notification {
  view() {
    var notification = this.props.notification;
    var discussion = notification.subject();

    return super.view({
      href: app.route('discussion.near', {
        id: discussion.id(),
        slug: discussion.slug(),
        near: notification.content().number
      }),
      config: m.route,
      title: notification.content().oldTitle,
      icon: 'pencil',
      content: ['Renamed by ', username(notification.sender())]
    });
  }
}
