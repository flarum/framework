import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import type Discussion from 'flarum/common/models/Discussion';
import { t } from '../t';

export default class IdeaShippedNotification extends Notification {
  icon() {
    return 'fas fa-rocket';
  }

  href() {
    const discussion = this.attrs.notification.subject() as Discussion | null;

    return discussion ? app.route.discussion(discussion) : '#';
  }

  content() {
    const discussion = this.attrs.notification.subject() as Discussion | null;

    return t('notifications.shipped_text', { title: discussion?.title() ?? '' });
  }

  excerpt() {
    return null;
  }
}
