import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';
import categoryLabel from 'flarum-categories/helpers/category-label';

export default class DiscussionMovedNotification extends Notification {
  view() {
    var notification = this.props.notification;
    var discussion = notification.subject();

    return super.view({
      href: app.route.discussion(discussion, notification.content().postNumber),
      icon: 'arrow-right',
      content: [username(notification.sender()), ' moved to ', categoryLabel(discussion.category())]
    });
  }
}
