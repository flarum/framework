import Notification from 'flarum/components/notification';
import avatar from 'flarum/helpers/avatar';
import icon from 'flarum/helpers/icon';
import username from 'flarum/helpers/username';
import humanTime from 'flarum/helpers/human-time';
import categoryLabel from 'categories/helpers/category-label';

export default class NotificationDiscussionMoved extends Notification {
  content() {
    var notification = this.props.notification;
    var discussion = notification.subject();
    var category = discussion.category();

    return m('a', {href: app.route('discussion.near', {
      id: discussion.id(),
      slug: discussion.slug(),
      near: notification.content().postNumber
    }), config: m.route}, [
      avatar(notification.sender()),
      m('h3.notification-title', discussion.title()),
      m('div.notification-info', [
        icon('arrow-right'),
        ' Moved to ', categoryLabel(category), ' by ', username(notification.sender()),
        ' ', humanTime(notification.time())
      ])
    ]);
  }
}
