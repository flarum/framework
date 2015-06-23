import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class PostLikedNotification extends Notification {
  view() {
    var notification = this.props.notification;
    var post = notification.subject();
    var auc = notification.additionalUnreadCount();

    return super.view({
      href: app.route.post(post),
      icon: 'thumbs-o-up',
      content: [username(notification.sender()), auc ? ' and '+auc+' others' : '', ' liked your post #', post.number()]
    });
  }
}
