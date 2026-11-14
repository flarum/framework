import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';
import { truncate } from 'flarum/common/utils/string';
import type { ModelIdentifier } from 'flarum/common/Model';
import type DialogMessage from '../../common/models/DialogMessage';

/** With flarum/likes: someone liked one of the member's messages. */
export default class MessageLikedNotification extends Notification {
  icon() {
    return 'far fa-thumbs-up';
  }

  href() {
    const message = this.attrs.notification.subject() as DialogMessage;
    // Notifications don't include the conversation itself, only its id.
    const dialogId = (message.data.relationships?.dialog?.data as ModelIdentifier | undefined)?.id;

    return dialogId ? app.route('dialog.message', { id: dialogId, near: message.number() }) : app.route('messages');
  }

  content() {
    return app.translator.trans('flarum-messages.forum.notifications.message_liked_text', { user: this.attrs.notification.fromUser() });
  }

  excerpt() {
    return truncate((this.attrs.notification.subject() as DialogMessage).contentPlain() || '', 200);
  }
}
