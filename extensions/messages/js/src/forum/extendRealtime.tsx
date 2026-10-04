import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';
import addRealtimeTypingIndicator from './addRealtimeTypingIndicator';
import type Dialog from '../common/models/Dialog';
import type DialogMessage from '../common/models/DialogMessage';

const MESSAGE_CREATED_EVENT = 'Flarum\\Messages\\DialogMessage\\Event\\Created';

export default function extendRealtime() {
  new RealtimeExtend()
    .onUserChannelEvent(MESSAGE_CREATED_EVENT, (data: unknown) => onMessageCreated(data as any))
    .extend(app, { name: 'flarum-messages', exports: {} });

  // Bind the new message event on MessageStream so we can access
  // the stream state directly and push the message in without a full reload.
  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function (this: any) {
    this.messageCreatedHandler = (data: unknown) => {
      const message = app.store.pushPayload(data as any) as any;

      if (message?.dialog?.()?.id() === this.attrs?.dialog?.id() && this.attrs.state.hasItems()) {
        this.attrs.state.push(message);
        setTimeout(() => this.scrollToBottom(), 50);
      }
    };
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oncreate', function (this: any) {
    app.websocket_channels?.user?.bind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: any) {
    app.websocket_channels?.user?.unbind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
  });

  addRealtimeTypingIndicator();
}

/** The dropdown shows this many unread dialogs. */
const DROPDOWN_LIMIT = 5;

/**
 * Brings a new message into every list that shows its dialog, in place: the
 * payload carries the message and its dialog (with this member's unread
 * count), so only a dialog new to this browser needs fetching, for its
 * participants.
 */
function onMessageCreated(data: any): void {
  const dialogId: string | undefined = data?.data?.relationships?.dialog?.data?.id;

  if (!dialogId) return;

  const known = app.store.getById<Dialog>('dialogs', dialogId);
  const wasUnread = !!known?.unreadCount();
  const message = app.store.pushPayload<DialogMessage>(data) as DialogMessage;
  const fromSelf = message.user() === app.session.user;

  const show = (dialog: Dialog) => {
    dialog.pushData({ relationships: { lastMessage: { data: { type: 'dialog-messages', id: message.id()! } } } } as any);

    app.dialogs.moveToTop(dialog);
    if (!fromSelf) app.dropdownDialogs.moveToTop(dialog, DROPDOWN_LIMIT);

    m.redraw();
  };

  if (!fromSelf && !wasUnread) {
    app.session.user!.pushAttributes({ messageCount: (app.session.user!.attribute<number>('messageCount') ?? 0) + 1 });
  }

  // A dialog this browser hasn't seen needs its participants for the list.
  if (known && known.users()) {
    show(known);
  } else {
    app.store
      .find<Dialog>('dialogs', dialogId, { include: 'users.groups,lastMessage' })
      .then(show)
      .catch(() => {});
  }
}
