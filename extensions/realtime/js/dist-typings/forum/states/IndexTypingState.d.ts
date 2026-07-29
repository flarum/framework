interface PresenceMap {
    [discussionId: string]: number;
}
/**
 * Tracks which discussions currently have someone typing, for the ambient dot on
 * the discussion list. Fed by the single `index-typing` channel (see Application),
 * which delivers coalesced, presence-only signals — no names, no per-discussion
 * subscriptions. A single shared instance backs every DiscussionListItem.
 */
export default class IndexTypingState {
    protected typing: PresenceMap;
    protected truncationTimer: ReturnType<typeof setTimeout> | null;
    /**
     * Record a coalesced presence signal from the index channel.
     */
    set(discussionId: number | string, typing: boolean): void;
    /**
     * Whether someone is currently typing in the given discussion. Prunes expired
     * entries and schedules a redraw for when the most recent entry will expire, so
     * a stale dot clears itself even if the falling-edge event never arrives.
     */
    isTyping(discussionId: number | string): boolean;
}
export {};
