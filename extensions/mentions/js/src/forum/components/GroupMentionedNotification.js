import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import { truncate } from 'flarum/common/utils/string';

export default class GroupMentionedNotification extends Notification {
  icon() {
    return 'fas fa-at';
  }

  href() {
    const post = this.attrs.notification.subject();

    return app.route.discussion(post.discussion(), post.number());
  }

  content() {
    const user = this.attrs.notification.fromUser();

    return app.translator.trans('flarum-mentions.forum.notifications.group_mentioned_text', { user });
  }

  excerpt() {
    return truncate(this.attrs.notification.subject().contentPlain(), 200);
  }
}
