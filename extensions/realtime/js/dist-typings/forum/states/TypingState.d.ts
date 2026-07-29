export interface TypingUserMap {
    [displayName: string]: number;
}
export interface TypingData {
    displayName: string;
    discloseOnline: boolean;
    time: number;
}
/**
 * Holds the set of users currently typing in a discussion.
 *
 * The realtime socket feeds incoming `client-typing` events in via {@link add},
 * and the TypingIndicator component reads the live, expiry-pruned set via
 * {@link active}. Keeping this state separate from PostStream lets the indicator
 * be rendered anywhere — a theme or extension can hold its own TypingState and
 * pass it to <TypingIndicator state={...} /> without touching PostStream.
 */
export default class TypingState {
    protected usersTyping: TypingUserMap;
    protected truncationTimer: ReturnType<typeof setTimeout> | null;
    /**
     * Record an incoming typing event. When the sender has not disclosed their
     * online status, their name is replaced with the anonymous placeholder.
     */
    add(data: TypingData): void;
    /**
     * The users currently typing, with expired entries pruned. Schedules a redraw
     * for when the most recent entry will expire, so the indicator clears itself.
     */
    active(): TypingUserMap;
    /**
     * Clear any pending expiry timer. Call when the owner is torn down.
     */
    dispose(): void;
}
