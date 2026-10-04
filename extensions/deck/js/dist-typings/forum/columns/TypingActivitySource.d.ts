import type Mithril from 'mithril';
import type { DeckColumnSource } from './DeckColumnType';
interface TypingActivityData {
    userId: number | null;
    displayName: string | null;
    discussionId: number | null;
    tagIds: number[] | null;
}
interface Entry extends TypingActivityData {
    key: string;
    at: Date;
}
interface Channel {
    bind(event: string, callback: (data: TypingActivityData) => void): void;
    unbind(event: string, callback: (data: TypingActivityData) => void): void;
}
/**
 * Who is typing where, forum-wide, from flarum/realtime's typing-activity
 * channel. The server only sends each subscriber what they could see anyway
 * (discussions they can see, names of members hiding their online status only
 * with `user.viewLastSeenAt`), so this renders what arrives as it is.
 */
export default class TypingActivitySource implements DeckColumnSource {
    protected entries: Entry[];
    protected socket: any;
    protected channel: Channel | null;
    protected requested: Set<number>;
    protected expiryTimer: number | null;
    protected pruneTimer: number | null;
    load(): Promise<unknown>;
    /** Subscribes, or re-subscribes after realtime has replaced its socket. */
    start(): void;
    stop(): void;
    /** Drops entries older than MAX_AGE_MS; true if any went. */
    protected prune(): boolean;
    /** Fed by its own channel; nothing else concerns it. */
    onRealtime(): void;
    checkForNew(): Promise<number>;
    showNew(): Promise<unknown>;
    view(): Mithril.Children;
    protected describe(entry: Entry, active: boolean): Mithril.Children;
    protected onActivity: (data: TypingActivityData) => void;
}
export {};
