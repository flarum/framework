/**
 * Ambient "someone is typing here" dot on the index sidebar tag list.
 *
 * Presence comes from the same `index-typing` channels as the discussion-list
 * dot (see Application), which now carry the tags a typing discussion belongs to.
 * This extender only *reads* the shared IndexTagTypingState — there are no
 * per-tag subscriptions beyond the ones realtime already makes, and the backend
 * scopes the tag IDs per channel so a restricted tag is never surfaced to an
 * audience that can't see it.
 *
 * The sidebar tag list is short and always on screen, so (unlike the
 * discussion-list dot) it needs no IntersectionObserver or SubtreeRetainer:
 * IndexTagTypingState.set()/isTyping() drive m.redraw() directly, including the
 * self-clearing redraw when the dot should disappear.
 *
 * Only loaded when flarum-tags is active (see forum/index.ts).
 */
export default function (): void;
