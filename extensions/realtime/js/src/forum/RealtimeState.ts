import type { Channel } from 'pusher-js';

type ChannelReadyCallback = (channel: Channel) => void;

/**
 * Singleton that tracks realtime channel state and holds registrations from
 * extensions. It decouples the channel setup timing (which happens during
 * Application.mount) from extender registration (which happens at boot, before
 * mount).
 *
 * Extensions interact with this indirectly via the Realtime JS extender.
 * Internal realtime code calls the `notify*` methods once channels are ready.
 */
class RealtimeState {
  private userChannelCallbacks: ChannelReadyCallback[] = [];
  private publicChannelCallbacks: ChannelReadyCallback[] = [];
  private discussionStreamEventNames: Set<string> = new Set();

  private userChannel: Channel | null = null;
  private publicChannel: Channel | null = null;

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
   * Run `callback` once the user private channel is ready, or immediately if
   * it is already established.
   */
  onUserChannelReady(callback: ChannelReadyCallback): void {
    if (this.userChannel) {
      callback(this.userChannel);
    } else {
      this.userChannelCallbacks.push(callback);
    }
  }

  /**
   * Run `callback` once the public channel is ready, or immediately if it is
   * already established.
   */
  onPublicChannelReady(callback: ChannelReadyCallback): void {
    if (this.publicChannel) {
      callback(this.publicChannel);
    } else {
      this.publicChannelCallbacks.push(callback);
    }
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
   * Called by Application.ts once the user private channel is subscribed.
   * Flushes any pending callbacks.
   */
  notifyUserChannelReady(channel: Channel): void {
    this.userChannel = channel;
    for (const cb of this.userChannelCallbacks) {
      cb(channel);
    }
    this.userChannelCallbacks = [];
  }

  /**
   * Called by Application.ts once the public channel is subscribed.
   * Flushes any pending callbacks.
   */
  notifyPublicChannelReady(channel: Channel): void {
    this.publicChannel = channel;
    for (const cb of this.publicChannelCallbacks) {
      cb(channel);
    }
    this.publicChannelCallbacks = [];
  }
}

export default new RealtimeState();
