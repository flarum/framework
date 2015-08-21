import Notification from 'flarum/components/Notification';
import username from 'flarum/helpers/username';
import punctuate from 'flarum/helpers/punctuate';

export default class PostMentionedNotification extends Notification {
  icon() {
    return 'reply';
  }

  href() {
    const notification = this.props.notification;
    const post = notification.subject();
    const auc = notification.additionalUnreadCount();
    const content = notification.content();

    return app.route.discussion(post.discussion(), auc ? post.number() : (content && content.replyNumber));
  }

  content() {
    const notification = this.props.notification;
    const auc = notification.additionalUnreadCount();
    const user = notification.sender();

    return app.trans('mentions.post_mentioned_notification', {
      user,
      username: auc ? punctuate([
        username(user),
        app.trans('mentions.others', {count: auc})
      ]) : undefined
    });
  }

  excerpt() {
    return this.props.notification.subject().contentPlain();
  }
}
