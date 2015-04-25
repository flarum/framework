import Notification from 'flarum/components/notification';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/human-time';

export default class NotificationDiscussionRenamed extends Notification {
  content() {
    var notification = this.props.notification;
    var discussion = notification.subject();

    return m('a', {href: app.route('discussion.near', {
      id: discussion.id(),
      slug: discussion.slug(),
      near: notification.content().number
    }), config: m.route}, [
      avatar(notification.sender()),
      m('h3.notification-title', notification.content().oldTitle),
      m('div.notification-info', [
        icon('pencil'),
        ' Renamed by ', username(notification.sender()),
        notification.additionalUnreadCount() ? ' and '+notification.additionalUnreadCount()+' others' : '',
        ' ', humanTime(notification.time())
      ])
    ]);
  }

  read() {
    this.props.notification.save({isRead: true});
  }
}
