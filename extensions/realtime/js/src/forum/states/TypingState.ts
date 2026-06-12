import app from 'flarum/forum/app';

export interface TypingUserMap {
  [displayName: string]: number;
}

export interface TypingData {
  displayName: string;
  discloseOnline: boolean;
  time: number;
}

/**
 * Entries older than this (ms) are considered no longer typing.
 */
const EXPIRY_MS = 6000;

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
  protected usersTyping: TypingUserMap = {};
  protected truncationTimer: ReturnType<typeof setTimeout> | null = null;

  /**
   * Record an incoming typing event. When the sender has not disclosed their
   * online status, their name is replaced with the anonymous placeholder.
   */
  add(data: TypingData): void {
    if (!data.discloseOnline) {
      data.displayName = String(app.translator.trans('flarum-realtime.forum.typing-indicator.anonymous-user'));
    }

    this.usersTyping[data.displayName] = data.time;
    m.redraw();
  }

  /**
   * The users currently typing, with expired entries pruned. Schedules a redraw
   * for when the most recent entry will expire, so the indicator clears itself.
   */
  active(): TypingUserMap {
    const invalidateWhen = Date.now() - EXPIRY_MS;
    let latestTime: number | null = null;

    for (const displayName of Object.keys(this.usersTyping)) {
      if (this.usersTyping[displayName] < invalidateWhen) {
        delete this.usersTyping[displayName];
      } else if (!latestTime || latestTime < this.usersTyping[displayName]) {
        latestTime = this.usersTyping[displayName];
      }
    }

    if (this.truncationTimer) {
      clearTimeout(this.truncationTimer);
      this.truncationTimer = null;
    }

    if (latestTime) {
      this.truncationTimer = setTimeout(() => m.redraw(), latestTime - Date.now());
    }

    return this.usersTyping;
  }

  /**
   * Clear any pending expiry timer. Call when the owner is torn down.
   */
  dispose(): void {
    if (this.truncationTimer) {
      clearTimeout(this.truncationTimer);
      this.truncationTimer = null;
    }
  }
}
