import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type Mithril from 'mithril';
import type ItemList from 'flarum/common/utils/ItemList';
import RealtimeExtend from 'ext:flarum/realtime/forum/extenders/Realtime';
import RealtimeState from 'ext:flarum/realtime/forum/RealtimeState';
import addRealtimeTypingIndicator from './addRealtimeTypingIndicator';
import onMessageCreated from './onMessageCreated';
import onMessageLikesChanged from './onMessageLikesChanged';
import type DialogMessage from '../common/models/DialogMessage';
import type MessageStream from './components/MessageStream';

const MESSAGE_CREATED_EVENT = 'Flarum\\Messages\\DialogMessage\\Event\\Created';
/** Raised only with flarum/likes enabled. */
const MESSAGE_LIKE_EVENTS = ['Flarum\\Messages\\DialogMessage\\Event\\Liked', 'Flarum\\Messages\\DialogMessage\\Event\\Unliked'];

/** A MessageStream, with what the live connection keeps on it. */
type LiveStream = MessageStream & {
  messageCreatedHandler: (data: unknown) => void;
  messageDisposeReconnect: (() => void) | null;
  bindMessageCreated(): void;
  unbindMessageCreated(): void;
};

export default function extendRealtime() {
  const realtime = new RealtimeExtend().onUserChannelEvent(MESSAGE_CREATED_EVENT, (data: unknown) => onMessageCreated(data as any));

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
    this.bindMessageCreated();

    // A reconnect replaces the channel objects: bind on the new one, and bring
    // in what was sent meanwhile.
    this.messageDisposeReconnect = RealtimeState.onChannelsReconnected(() => {
      this.bindMessageCreated();
      this.attrs.state
        .catchUp()
        // With likes, messages already shown change too, and those changes
        // were missed as well.
        .then(() => ('flarum-likes' in flarum.extensions ? this.attrs.state.refreshHeld() : undefined))
        .then(() => this.afterRedraw(() => this.markAsRead()));
    });
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: LiveStream) {
    this.messageDisposeReconnect?.();
    this.messageDisposeReconnect = null;
    this.unbindMessageCreated();
  });

  addRealtimeTypingIndicator();
}
