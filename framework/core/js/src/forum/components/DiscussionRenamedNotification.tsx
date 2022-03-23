import type Discussion from '../../common/models/Discussion';
import app from '../../forum/app';
import Notification from './Notification';

interface DiscussionRenamedContent {
  postNumber: number;
}

/**
 * The `DiscussionRenamedNotification` component displays a notification which
 * indicates that a discussion has had its title changed.
 */
export default class DiscussionRenamedNotification extends Notification {
  icon() {
    return 'fas fa-pencil-alt';
  }

  href() {
    const notification = this.attrs.notification;
    const discussion = notification.subject();

    if (!discussion) {
      return '#';
    }

    return app.route.discussion(discussion as Discussion, notification.content<DiscussionRenamedContent>().postNumber);
  }

  content() {
    return app.translator.trans('core.forum.notifications.discussion_renamed_text', { user: this.attrs.notification.fromUser() });
  }

  excerpt() {
    return null;
  }
}
