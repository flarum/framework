interface PresenceMap {
    [tagId: string]: number;
}
/**
 * Tracks which tags currently have someone typing in one of their discussions,
 * for the ambient dot on the index sidebar tag list. Fed by the same
 * `index-typing` channels as IndexTypingState (see Application), which now carry
 * the tag IDs a typing discussion belongs to alongside its discussion id —
 * scoped per channel so restricted tags are only ever surfaced to an audience
 * that can see them.
 *
 * Several discussions can be typed in under one tag at once, so unlike the
 * discussion-keyed state we count how many discussions are keeping each tag
 * "warm": the dot clears only once the last of them falls idle.
 */
export default class IndexTagTypingState {
    /** tagId => latest ping timestamp (ms), refreshed by any typing discussion under it. */
    protected typing: PresenceMap;
    /**
     * tagId => set of discussion ids currently typing under it. A tag stays lit
     * while any discussion is active, and only clears when the set empties.
     */
    protected discussions: {
        [tagId: string]: Set<string>;
    };
    protected truncationTimer: ReturnType<typeof setTimeout> | null;
    /**
     * Record a coalesced presence signal for a discussion against the tags it
     * belongs to. The same discussion's rising/falling edges add and remove it
     * from each tag's active set.
     */
    set(source: number | string, tagIds: Array<number | string>, typing: boolean): void;
    /**
     * Whether someone is currently typing in a discussion under the given tag.
     * Prunes expired entries and schedules a redraw for when the most recent entry
     * will expire, so a stale dot clears itself even if a falling-edge event never
     * arrives.
     */
    isTyping(tagId: number | string): boolean;
}
export {};
