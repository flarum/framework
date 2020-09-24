import Notification from 'flarum/components/Notification';
import { truncate } from 'flarum/utils/string';

export default class UserMentionedNotification extends Notification {
  icon() {
    return 'fas fa-at';
  }

  href() {
    const post = this.attrs.notification.subject();

    return app.route.discussion(post.discussion(), post.number());
  }

  content() {
    const user = this.attrs.notification.fromUser();

    return app.translator.trans('flarum-mentions.forum.notifications.user_mentioned_text', {user});
  }

  excerpt() {
    return truncate(this.attrs.notification.subject().contentPlain(), 200);
  }
}
