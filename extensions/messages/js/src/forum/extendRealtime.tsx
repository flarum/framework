import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';
import RealtimeState from 'ext:flarum/realtime/forum/RealtimeState';
import addRealtimeTypingIndicator from './addRealtimeTypingIndicator';
import onMessageCreated from './onMessageCreated';
import onMessageLikesChanged from './onMessageLikesChanged';
import onMessageChanged from './onMessageChanged';
import onMessageDeleted from './onMessageDeleted';
import { forgetDialog } from './utils/removeMessage';
import { onReadSignal, refreshReceipts } from './utils/readReceipts';
import type DialogMessage from '../common/models/DialogMessage';
import type MessageStream from './components/MessageStream';

const MESSAGE_CREATED_EVENT = 'Flarum\\Messages\\DialogMessage\\Event\\Created';
/** Raised only with flarum/likes enabled. */
const MESSAGE_LIKE_EVENTS = ['Flarum\\Messages\\DialogMessage\\Event\\Liked', 'Flarum\\Messages\\DialogMessage\\Event\\Unliked'];
/** Edited, deleted (in a way that can be undone) and restored. */
const MESSAGE_CHANGED_EVENTS = [
  'Flarum\\Messages\\DialogMessage\\Event\\Revised',
  'Flarum\\Messages\\DialogMessage\\Event\\Hidden',
  'Flarum\\Messages\\DialogMessage\\Event\\Restored',
];
/** Deleted for good. */
const MESSAGE_DELETED_EVENT = 'Flarum\\Messages\\DialogMessage\\Event\\Deleted';
/** Sent to the authors of what was read. */
const USER_READ_EVENT = 'Flarum\\Messages\\Dialog\\Event\\UserRead';

/** The conversation open on screen, if any, for removals to take messages out of. */
let openStream: MessageStream | null = null;

/** A MessageStream, with what the live connection keeps on it. */
type LiveStream = MessageStream & {
  messageCreatedHandler: (data: unknown) => void;
  messageDisposeReconnect: (() => void) | null;
  bindMessageCreated(): void;
  unbindMessageCreated(): void;
};

export default function extendRealtime() {
  const realtime = new RealtimeExtend().onUserChannelEvent(MESSAGE_CREATED_EVENT, (data: unknown) => onMessageCreated(data as any));

  MESSAGE_CHANGED_EVENTS.forEach((event) => realtime.onUserChannelEvent(event, onMessageChanged));
  realtime.onUserChannelEvent(MESSAGE_DELETED_EVENT, (data: unknown) => onMessageDeleted(data as any, openStream?.attrs.state));
  realtime.onUserChannelEvent(USER_READ_EVENT, (data: unknown) => onReadSignal(data, (dialog) => openStream?.attrs.dialog === dialog));

  if ('flarum-likes' in flarum.extensions) {
    MESSAGE_LIKE_EVENTS.forEach((event) => realtime.onUserChannelEvent(event, onMessageLikesChanged));
  }

  realtime.extend(app, { name: 'flarum-messages', exports: {} });

  // Pusher buffers nothing: whatever arrived while the socket was down is
  // gone, so after a reconnect the lists are refetched in the background, and
  // the member with them, for their unread count.
  RealtimeState.onChannelsReconnected(() => {
    if (app.dialogs.hasItems()) app.dialogs.revalidate();
    if (app.dropdownDialogs.hasItems()) app.dropdownDialogs.revalidate();
    if (app.session.user) app.store.find('users', app.session.user.id()!).catch(() => {});
  });

  // The list is kept current as messages arrive, and refetched after a
  // reconnect, so there is nothing for a refresh button to do; as on the
  // discussion list.
  extend('ext:flarum/messages/forum/components/MessagesPage', 'actionItems', function (items: ItemList<Mithril.Children>) {
    items.remove('refresh');
  });

  // The open conversation takes new messages straight into its stream, from
  // the user channel, which only carries the messages of this member's dialogs.
  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function (this: LiveStream) {
    this.messageCreatedHandler = (data: unknown) => {
      const message = app.store.pushPayload<DialogMessage>(data as any) as DialogMessage;
      const dialog = message?.dialog?.();

      if (dialog && dialog.id() === this.attrs?.dialog?.id()) this.receive(message);
    };

    this.bindMessageCreated = () => app.websocket_channels?.user?.bind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
    this.unbindMessageCreated = () => app.websocket_channels?.user?.unbind(MESSAGE_CREATED_EVENT, this.messageCreatedHandler);
    this.messageDisposeReconnect = null;
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oncreate', function (this: LiveStream) {
    openStream = this;
    this.bindMessageCreated();

    // A reconnect replaces the channel objects: bind on the new one, and bring
    // in what was sent meanwhile.
    this.messageDisposeReconnect = RealtimeState.onChannelsReconnected(() => {
      this.bindMessageCreated();
      this.attrs.state
        .catchUp()
        // Messages already shown change too, edited, deleted or liked, and
        // those changes were missed as well.
        .then(() => this.attrs.state.refreshHeld())
        // After catchUp, whose answer may be older than reads heard meanwhile.
        .then(() => (this.attrs.dialog ? refreshReceipts(this.attrs.dialog) : undefined))
        // Nothing left of it: deleted meanwhile, perhaps with the conversation.
        .then(() => {
          const dialog = this.attrs.dialog;

          if (this.attrs.state.hasItems() || !dialog) return;

          return app.store
            .find('dialogs', dialog.id()!)
            .then(() => undefined)
            .catch(() => forgetDialog(dialog));
        })
        .then(() => this.afterRedraw(() => this.markAsRead()));
    });
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: LiveStream) {
    if (openStream === this) openStream = null;
    this.messageDisposeReconnect?.();
    this.messageDisposeReconnect = null;
    this.unbindMessageCreated();
  });

  addRealtimeTypingIndicator();
}
