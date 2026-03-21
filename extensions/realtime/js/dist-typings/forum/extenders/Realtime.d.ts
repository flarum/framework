import type IExtender from 'flarum/common/extenders/IExtender';
import type { IExtensionModule } from 'flarum/common/extenders/IExtender';
import type ForumApplication from 'flarum/forum/ForumApplication';
type ChannelEventCallback = (data: unknown) => void;
/**
 * JS extender for integrating with flarum/realtime.
 *
 * Use this in your extension's `extend.ts`, guarded by an extension check:
 *
 * ```ts
 * import { RealtimeExtend } from 'flarum/realtime/forum';
 *
 * export default [
 *   ...('flarum-realtime' in flarum.extensions
 *     ? [
 *         new RealtimeExtend()
 *           .onDiscussionStreamEvent('likesMutation'),
 *       ]
 *     : []),
 * ];
 * ```
 *
 * The guard is necessary because this module will not be available when
 * flarum/realtime is not installed.
 */
export default class Realtime implements IExtender<ForumApplication> {
    /**
     * Events that should trigger a discussion stream update on DiscussionPage.
     */
    private discussionStreamEvents;
    /**
     * Arbitrary channel event bindings.
     */
    private channelBindings;
    /**
     * Register a Pusher event name that should trigger a discussion stream
     * reload on the currently open DiscussionPage.
     *
     * The event is bound on both the public and user channels. When received,
     * DiscussionPage reloads the post stream so the UI reflects the change
     * (e.g. a post being liked, locked, voted on).
     *
     * @param eventName  The Pusher event name, e.g. 'likesMutation'.
     */
    onDiscussionStreamEvent(eventName: string): this;
    /**
     * Bind a callback to an event on the user's private channel.
     *
     * @param eventName  The Pusher event name.
     * @param callback   Called with the event payload when the event fires.
     */
    onUserChannelEvent(eventName: string, callback: ChannelEventCallback): this;
    /**
     * Bind a callback to an event on the public channel.
     *
     * @param eventName  The Pusher event name.
     * @param callback   Called with the event payload when the event fires.
     */
    onPublicChannelEvent(eventName: string, callback: ChannelEventCallback): this;
    /**
     * Bind a callback to an event on both the public and user channels.
     *
     * @param eventName  The Pusher event name.
     * @param callback   Called with the event payload when the event fires.
     */
    onBothChannelsEvent(eventName: string, callback: ChannelEventCallback): this;
    extend(app: ForumApplication, extension: IExtensionModule): void;
}
export {};
