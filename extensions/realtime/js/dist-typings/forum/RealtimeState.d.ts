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
declare class RealtimeState {
    private userChannelCallbacks;
    private publicChannelCallbacks;
    private discussionStreamEventNames;
    private userChannel;
    private publicChannel;
    /**
     * Register event names that should trigger a DiscussionPage stream reload.
     * Called by the Realtime extender on behalf of other extensions.
     */
    registerDiscussionStreamEvents(eventNames: string[]): void;
    /**
     * Run `callback` once the user private channel is ready, or immediately if
     * it is already established.
     */
    onUserChannelReady(callback: ChannelReadyCallback): void;
    /**
     * Run `callback` once the public channel is ready, or immediately if it is
     * already established.
     */
    onPublicChannelReady(callback: ChannelReadyCallback): void;
    /**
     * Returns all discussion stream event names registered by extensions.
     * Used by Discussion/NewActivity to know which events to bind.
     */
    getDiscussionStreamEventNames(): string[];
    /**
     * Called by Application.ts once the user private channel is subscribed.
     * Flushes any pending callbacks.
     */
    notifyUserChannelReady(channel: Channel): void;
    /**
     * Called by Application.ts once the public channel is subscribed.
     * Flushes any pending callbacks.
     */
    notifyPublicChannelReady(channel: Channel): void;
}
declare const _default: RealtimeState;
export default _default;
