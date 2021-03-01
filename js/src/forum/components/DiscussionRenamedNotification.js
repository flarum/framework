import Notification from './Notification';

/**
 * The `DiscussionRenamedNotification` component displays a notification which
 * indicates that a discussion has had its title changed.
 *
 * ### Attrs
 *
 * - All of the attrs for Notification
 */
export default class DiscussionRenamedNotification extends Notification {
  icon() {
    return 'fas fa-pencil-alt';
  }

  href() {
    const notification = this.attrs.notification;

    return app.route.discussion(notification.subject(), notification.content().postNumber);
  }

  content() {
    return app.translator.trans('core.forum.notifications.discussion_renamed_text', { user: this.attrs.notification.fromUser() });
  }
}
