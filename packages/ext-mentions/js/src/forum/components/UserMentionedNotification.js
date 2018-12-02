import Notification from 'flarum/components/Notification';
import { truncate } from 'flarum/utils/string';

export default class UserMentionedNotification extends Notification {
  icon() {
    return 'fas fa-at';
  }

  href() {
    const post = this.props.notification.subject();

    return app.route.discussion(post.discussion(), post.number());
  }

  content() {
    const user = this.props.notification.fromUser();

    return app.translator.trans('flarum-mentions.forum.notifications.user_mentioned_text', {user});
  }

  excerpt() {
    return truncate(this.props.notification.subject().contentPlain(), 200);
  }
}
