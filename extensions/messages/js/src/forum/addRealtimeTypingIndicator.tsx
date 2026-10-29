import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import RealtimeState from 'ext:flarum/realtime/forum/RealtimeState';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
import TypingState, { announces } from './utils/TypingState';
import type Dialog from '../common/models/Dialog';
import type MessageStream from './components/MessageStream';

const TYPING_EVENT = 'client-typing';
/** How often the draft is checked for changes. */
const CHECK_EVERY_MS = 1000;
/** The least time between two signals from this member. */
const SIGNAL_EVERY_MS = 2000;
/** Names shown before falling back to "and N others". */
const MAX_NAMED = 3;

/** A MessageStream, with what the typing indicator keeps on it. */
type TypingStream = MessageStream & {
  typingState: TypingState;
  /** The conversation's channel, and the identified one for those who may see hidden typists. */
  typingChannels: { bind: Function; unbind: Function; trigger: Function; unsubscribe: Function }[];
  typingHandler: (data: unknown) => void;
  typingDisposeReconnect: (() => void) | null;
  typingCheckTimer: number | null;
  typingExpiryTimer: number | null;
  typingPrevious: string;
  typingDirty: boolean;
  typingSentAt: number;
  subscribeTyping(): void;
  unsubscribeTyping(): void;
  checkTyping(): void;
};

function typingChannelName(dialog: Dialog): string {
  return 'private-privateMessageTyping=' + dialog.id();
}

/** Names members who type while hiding their online status; only holders of `user.viewLastSeenAt` may join. */
function identifiedTypingChannelName(dialog: Dialog): string {
  return 'private-privateMessageTypingIdentified=' + dialog.id();
}

/**
 * A typing indicator in a conversation, when flarum/realtime is enabled.
 * Members signal on the conversation's own private channel, which only its
 * members may join. The realtime server replaces the signal with who sent it,
 * as the socket knows them, and honours their online-status preference the
 * way it does for discussions; see TypingState for the receiving side.
 */
export default function addRealtimeTypingIndicator() {
  extend('ext:flarum/messages/forum/components/MessageStream', 'oninit', function (this: TypingStream) {
    this.typingState = new TypingState(
      () => (this.attrs.dialog.users() || []).filter((user): user is User => !!user),
      () => app.session.user?.id()
    );
    this.typingChannels = [];
    this.typingDisposeReconnect = null;
    this.typingCheckTimer = null;
    this.typingExpiryTimer = null;
    this.typingPrevious = '';
    this.typingDirty = false;
    this.typingSentAt = 0;

    this.typingHandler = (data: unknown) => {
      if (this.typingState.received(data)) m.redraw();
    };

    this.subscribeTyping = () => {
      this.unsubscribeTyping();

      const names = [typingChannelName(this.attrs.dialog)];

      // Subscription is authorised server-side; the attribute just avoids
      // asking for a channel we'd be refused.
      if (app.session.user?.attribute('canViewHiddenTypers')) names.push(identifiedTypingChannelName(this.attrs.dialog));

      // Kept on this instance: a shared slot was unsubscribed by the stream
      // being torn down, taking the one just set up for the next dialog with it.
      this.typingChannels = names.map((name) => app.websocket?.subscribe(name)).filter((channel) => !!channel);
      this.typingChannels.forEach((channel) => channel.bind(TYPING_EVENT, this.typingHandler));
    };

    this.unsubscribeTyping = () => {
      this.typingChannels.forEach((channel) => {
        channel.unbind(TYPING_EVENT, this.typingHandler);
        channel.unsubscribe();
      });
      this.typingChannels = [];
    };

    // Only this conversation's own draft counts: the composer may be open on a
    // discussion instead, and typing there is not typing here.
    this.checkTyping = () => {
      const composer = app.composer;

      if (!composer.isVisible() || !composer.bodyMatches('flarum/messages/forum/components/MessageComposer', { replyingTo: this.attrs.dialog })) {
        this.typingPrevious = '';
        this.typingDirty = false;

        return;
      }

      const content = composer.fields?.content?.() ?? '';

      if (announces(this.typingPrevious, content)) this.typingDirty = true;
      this.typingPrevious = content;

      const now = Date.now();

      if (this.typingDirty && now - this.typingSentAt >= SIGNAL_EVERY_MS) {
        // Nothing to say but "typing": the server adds who, from the socket.
        this.typingChannels[0]?.trigger(TYPING_EVENT, {});
        this.typingDirty = false;
        this.typingSentAt = now;
      }
    };
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'oncreate', function (this: TypingStream) {
    if (app.forum.attribute('websocket.disallow_connection')) return;
    if (!this.attrs?.dialog) return;

    this.subscribeTyping();
    // A reconnect replaces the Pusher instance and its channels; this one has to be set up again on the new one.
    this.typingDisposeReconnect = RealtimeState.onChannelsReconnected(() => this.subscribeTyping());
    this.typingCheckTimer = window.setInterval(() => this.checkTyping(), CHECK_EVERY_MS);
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'onremove', function (this: TypingStream) {
    if (this.typingCheckTimer) clearInterval(this.typingCheckTimer);
    if (this.typingExpiryTimer) clearTimeout(this.typingExpiryTimer);
    this.typingDisposeReconnect?.();
    this.typingDisposeReconnect = null;
    this.unsubscribeTyping();
  });

  extend('ext:flarum/messages/forum/components/MessageStream', 'content', function (this: TypingStream, items: Mithril.Children[]) {
    const users = this.typingState.users();

    // Redraw when the earliest signal expires, so the indicator clears itself.
    if (this.typingExpiryTimer) clearTimeout(this.typingExpiryTimer);
    const expiry = this.typingState.msUntilNextExpiry();
    this.typingExpiryTimer = expiry === null ? null : window.setTimeout(() => m.redraw(), expiry);

    const indicator = (
      <div className={classList('TypingUsersContainer', users.length > 0 && 'TypingUsersContainer-active')} key="typing">
        <div className="TypingUsers">
          <Icon name={users.length > 0 ? 'fas fa-ellipsis-h fa-beat' : 'fas fa-pause'} />
          {typingText(users)}
        </div>
      </div>
    );

    // Just above the reply box, when there is one.
    const reply = items.findIndex((item) => (item as Mithril.Vnode | null)?.key === 'reply');
    items.splice(reply === -1 ? items.length : reply, 0, indicator);
  });
}

function typingText(users: User[]): Mithril.Children {
  if (!users.length) return app.translator.trans('flarum-realtime.forum.typing-indicator.no-activity');

  // Realtime's own preference; the forum default is to name people.
  const named = app.session.user?.preferences()?.['flarum-realtime.typing-indicator-full'] ?? true;

  if (!named) return app.translator.trans('flarum-realtime.forum.typing-indicator.people-are-typing', { number: users.length });

  return app.translator.trans('flarum-realtime.forum.typing-indicator.users-are-typing', {
    users: users
      .slice(0, MAX_NAMED)
      .map((user) => user.displayName())
      .join(', '),
    count: users.length,
    others: Math.max(users.length - MAX_NAMED, 0),
  });
}
