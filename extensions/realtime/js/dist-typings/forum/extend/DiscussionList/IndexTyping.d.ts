/**
 * Ambient "someone is typing here" dot on discussion list rows.
 *
 * Presence comes from the single shared `index-typing` channel (see Application),
 * so there are no per-discussion subscriptions — this extender only *reads* the
 * shared IndexTypingState.
 *
 * DiscussionListItem retains its subtree (onbeforeupdate → SubtreeRetainer), so a
 * bare m.redraw() does NOT re-run infoItems. We register a SubtreeRetainer check
 * that tracks this discussion's typing flag (combined with the in-view flag) so
 * the row re-renders precisely when its dot should appear or disappear.
 *
 * To keep redraw work bounded on long lists, a row only consults the typing state
 * while it's actually in the viewport (tracked with an IntersectionObserver), so
 * off-screen rows never schedule the self-clearing redraw timers isTyping() sets up.
 */
export default function (): void;
