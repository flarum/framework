/**
 * Entries older than this (ms) are considered no longer typing. Acts as a
 * client-side backstop in case a falling-edge `index-typing` event is missed
 * (e.g. dropped during a reconnect), so a dot self-clears within this window.
 *
 * Mirrors TypingState's EXPIRY_MS so the list dot and the in-discussion
 * indicator clear on the same cadence.
 */
const EXPIRY_MS = 6000;

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
  protected typing: PresenceMap = {};
  protected truncationTimer: ReturnType<typeof setTimeout> | null = null;

  /**
   * Record a coalesced presence signal from the index channel.
   */
  set(discussionId: number | string, typing: boolean): void {
    const id = String(discussionId);

    if (typing) {
      this.typing[id] = Date.now();
    } else {
      delete this.typing[id];
    }

    m.redraw();
  }

  /**
   * Whether someone is currently typing in the given discussion. Prunes expired
   * entries and schedules a redraw for when the most recent entry will expire, so
   * a stale dot clears itself even if the falling-edge event never arrives.
   */
  isTyping(discussionId: number | string): boolean {
    const invalidateWhen = Date.now() - EXPIRY_MS;
    let latestTime: number | null = null;

    for (const id of Object.keys(this.typing)) {
      if (this.typing[id] < invalidateWhen) {
        delete this.typing[id];
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

    return Object.prototype.hasOwnProperty.call(this.typing, String(discussionId));
  }
}
