import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class NotificationDiscussionStickied extends Notification {
  view() {
    var notification = this.props.notification;
    var discussion = notification.subject();

    return super.view({
      href: app.route('discussion.near', {
        id: discussion.id(),
        slug: discussion.slug(),
        near: notification.content().postNumber
      }),
      config: m.route,
      title: discussion.title(),
      icon: 'thumb-tack',
      content: ['Stickied by ', username(notification.sender())]
    });
  }
}
