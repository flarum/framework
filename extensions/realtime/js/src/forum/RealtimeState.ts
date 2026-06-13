import type { Channel } from 'pusher-js';
import IndexTypingState from './states/IndexTypingState';

type ChannelReadyCallback = (channel: Channel) => void;
type ReconnectCallback = () => void;

/**
 * Singleton that tracks realtime channel state and holds registrations from
 * extensions. It decouples the channel setup timing (which happens during
 * Application.mount) from extender registration (which happens at boot, before
 * mount).
 *
 * Channel-ready callbacks are retained after the initial fire so that they
 * re-run when a reconnect cycle replaces the Pusher instance and produces
 * fresh channel objects (see Application.ts forceReconnect, flarum/framework
 * #4597). Bindings made against the previous channels become inert when the
 * old Pusher is GC'd, so re-firing the same callback against the new channel
 * is safe and produces no duplication.
 *
 * Extensions interact with this indirectly via the Realtime JS extender.
 * Internal realtime code calls the `notify*` methods once channels are ready.
 */
class RealtimeState {
  private userChannelCallbacks: ChannelReadyCallback[] = [];
  private publicChannelCallbacks: ChannelReadyCallback[] = [];
  private reconnectCallbacks: ReconnectCallback[] = [];
  private discussionStreamEventNames: Set<string> = new Set();

  private userChannel: Channel | null = null;
  private publicChannel: Channel | null = null;

  /**
   * Shared presence state for the discussion-list typing dots, fed by the
   * `index-typing` channel and read by DiscussionListItem. One instance backs
   * every list item.
   */
  readonly indexTyping = new IndexTypingState();

  // ---------------------------------------------------------------------------
  // Registration (called by extensions via the Realtime extender)
  // ---------------------------------------------------------------------------

  /**
   * Register event names that should trigger a DiscussionPage stream reload.
   * Called by the Realtime extender on behalf of other extensions.
   */
  registerDiscussionStreamEvents(eventNames: string[]): void {
    for (const name of eventNames) {
      this.discussionStreamEventNames.add(name);
    }
  }

  /**
   * Run `callback` once the user private channel is ready, then again every
   * time a reconnect cycle replaces the channel. If the channel is already
   * established when called, `callback` fires immediately as well.
   */
  onUserChannelReady(callback: ChannelReadyCallback): void {
    this.userChannelCallbacks.push(callback);
    if (this.userChannel) callback(this.userChannel);
  }

  /**
   * Run `callback` once the public channel is ready, then again every time a
   * reconnect cycle replaces the channel. If the channel is already
   * established when called, `callback` fires immediately as well.
   */
  onPublicChannelReady(callback: ChannelReadyCallback): void {
    this.publicChannelCallbacks.push(callback);
    if (this.publicChannel) callback(this.publicChannel);
  }

  /**
   * Register a callback to fire after `Application.forceReconnect` has built
   * a fresh Pusher instance and re-subscribed its channels. Components that
   * bind handlers from inside a lifecycle hook (`oncreate`) use this hook to
   * re-bind on the new channel objects.
   *
   * Returns a disposer that removes the callback. Call it from `onremove`.
   */
  onChannelsReconnected(callback: ReconnectCallback): () => void {
    this.reconnectCallbacks.push(callback);
    return () => {
      const i = this.reconnectCallbacks.indexOf(callback);
      if (i >= 0) this.reconnectCallbacks.splice(i, 1);
    };
  }

  // ---------------------------------------------------------------------------
  // Getters (called by internal realtime components)
  // ---------------------------------------------------------------------------

  /**
   * Returns all discussion stream event names registered by extensions.
   * Used by Discussion/NewActivity to know which events to bind.
   */
  getDiscussionStreamEventNames(): string[] {
    return Array.from(this.discussionStreamEventNames);
  }

  // ---------------------------------------------------------------------------
  // Notification (called by Application.ts once channels are subscribed)
  // ---------------------------------------------------------------------------

  /**
   * Called by Application.ts each time the user private channel is subscribed
   * — both at initial mount and after each reconnect cycle. Fires all
   * registered callbacks against the supplied channel.
   */
  notifyUserChannelReady(channel: Channel): void {
    this.userChannel = channel;
    for (const cb of this.userChannelCallbacks) {
      cb(channel);
    }
  }

  /**
   * Called by Application.ts each time the public channel is subscribed —
   * both at initial mount and after each reconnect cycle. Fires all
   * registered callbacks against the supplied channel.
   */
  notifyPublicChannelReady(channel: Channel): void {
    this.publicChannel = channel;
    for (const cb of this.publicChannelCallbacks) {
      cb(channel);
    }
  }

  /**
   * Called by Application.ts after a reconnect cycle has replaced the Pusher
   * instance and re-subscribed its channels. Fires all registered reconnect
   * callbacks so that lifecycle-bound consumers (e.g. NewActivity) can
   * re-attach their handlers to the new channels.
   */
  notifyChannelsReconnected(): void {
    for (const cb of this.reconnectCallbacks) {
      cb();
    }
  }
}

export default new RealtimeState();
