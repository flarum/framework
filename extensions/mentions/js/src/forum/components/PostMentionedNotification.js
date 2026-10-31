import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import { truncate } from 'flarum/common/utils/string';

export default class PostMentionedNotification extends Notification {
  icon() {
    return 'fas fa-reply';
  }

  href() {
    const notification = this.attrs.notification;
    const post = notification.subject();
    const content = notification.content();
    const near = content?.replyNumber;

    if (content?.discussionId) {
      return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
        id: content.discussionId,
        near: near && near !== 1 ? near : undefined,
      });
    }

    // Fallback for notifications created before discussionId was added to the payload.
    return app.route.discussion(post.discussion(), near);
  }

  content() {
    const notification = this.attrs.notification;
    const user = notification.fromUser();

    return app.translator.trans('flarum-mentions.forum.notifications.post_mentioned_text', { user, count: 1 });
  }

  excerpt() {
    return truncate(this.attrs.notification.subject().contentPlain() || '', 200);
  }
}
