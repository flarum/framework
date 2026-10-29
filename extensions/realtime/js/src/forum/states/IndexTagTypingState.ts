/**
 * Entries older than this (ms) are considered no longer typing. Acts as a
 * client-side backstop in case a falling-edge `index-typing` event is missed
 * (e.g. dropped during a reconnect), so a dot self-clears within this window.
 *
 * Mirrors IndexTypingState's EXPIRY_MS so the tag-list dot and the
 * discussion-list dot clear on the same cadence.
 */
const EXPIRY_MS = 6000;

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
  protected typing: PresenceMap = {};

  /**
   * tagId => set of discussion ids currently typing under it. A tag stays lit
   * while any discussion is active, and only clears when the set empties.
   */
  protected discussions: { [tagId: string]: Set<string> } = {};

  protected truncationTimer: ReturnType<typeof setTimeout> | null = null;

  /**
   * Record a coalesced presence signal for a discussion against the tags it
   * belongs to. The same discussion's rising/falling edges add and remove it
   * from each tag's active set.
   */
  set(source: number | string, tagIds: Array<number | string>, typing: boolean): void {
    // `source` identifies who is typing under each tag: a discussion id for replies,
    // or a per-user key (e.g. "u148") for someone composing a new discussion. A tag
    // stays lit while ANY source is active, so concurrent typers don't clear each
    // other's dot.
    const sourceKey = String(source);

    for (const rawTagId of tagIds) {
      const tagId = String(rawTagId);
      const set = (this.discussions[tagId] ??= new Set());

      if (typing) {
        set.add(sourceKey);
        this.typing[tagId] = Date.now();
      } else {
        set.delete(sourceKey);
        if (set.size === 0) {
          delete this.discussions[tagId];
          delete this.typing[tagId];
        }
      }
    }

    m.redraw();
  }

  /**
   * Whether someone is currently typing in a discussion under the given tag.
   * Prunes expired entries and schedules a redraw for when the most recent entry
   * will expire, so a stale dot clears itself even if a falling-edge event never
   * arrives.
   */
  isTyping(tagId: number | string): boolean {
    const invalidateWhen = Date.now() - EXPIRY_MS;
    let latestTime: number | null = null;

    for (const id of Object.keys(this.typing)) {
      if (this.typing[id] < invalidateWhen) {
        delete this.typing[id];
        delete this.discussions[id];
      } else if (!latestTime || latestTime < this.typing[id]) {
        latestTime = this.typing[id];
      }
    }

    if (this.truncationTimer) {
      clearTimeout(this.truncationTimer);
      this.truncationTimer = null;
    }

    if (latestTime) {
      this.truncationTimer = setTimeout(() => m.redraw(), latestTime - invalidateWhen);
    }

    return Object.prototype.hasOwnProperty.call(this.typing, String(tagId));
  }
}
