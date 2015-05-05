import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';
import categoryLabel from 'categories/helpers/category-label';

export default class NotificationDiscussionMoved extends Notification {
  view() {
    var notification = this.props.notification;
    var discussion = notification.subject();
    var category = discussion.category();

    return super.view({
      href: app.route('discussion.near', {
        id: discussion.id(),
        slug: discussion.slug(),
        near: notification.content().postNumber
      }),
      config: m.route,
      title: discussion.title(),
      icon: 'arrow-right',
      content: ['Moved to ', categoryLabel(category), ' by ', username(notification.sender())]
    });
  }
}
