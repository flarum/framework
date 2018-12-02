import Notification from 'flarum/components/Notification';
import { truncate } from 'flarum/utils/string';

export default class PostMentionedNotification extends Notification {
  icon() {
    return 'fas fa-reply';
  }

  href() {
    const notification = this.props.notification;
    const post = notification.subject();
    const content = notification.content();

    return app.route.discussion(post.discussion(), content && content.replyNumber);
  }

  content() {
    const notification = this.props.notification;
    const user = notification.fromUser();

    return app.translator.transChoice('flarum-mentions.forum.notifications.post_mentioned_text', 1, {user});
  }

  excerpt() {
    return truncate(this.props.notification.subject().contentPlain(), 200);
  }
}
