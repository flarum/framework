import Notification from 'flarum/components/notification';
import username from 'flarum/helpers/username';

export default class PostMentionedNotification extends Notification {
  view() {
    var notification = this.props.notification;
    var post = notification.subject();
    var auc = notification.additionalUnreadCount();
    var content = notification.content();

    return super.view({
      href: app.route.discussion(post.discussion(), auc ? post.number() : (content && content.replyNumber)),
      icon: 'reply',
      content: [username(notification.sender()), (auc ? ' and '+auc+' others' : '')+' replied to your post (#'+post.number()+')']
    });
  }
}
