import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';

export interface TypingUserMap {
  [displayName: string]: number;
}

export interface TypingData {
  /**
   * Null when the server withheld the identity: the user is hiding their online
   * status and we are not permitted to see through it.
   */
  displayName: string | null;
  discloseOnline: boolean;
  /**
   * When the sender believed they were typing, from their own `Date.now()`.
   *
   * Kept on the wire for compatibility, but never compared against our clock:
   * it comes from another machine, and any skew between the two would be read
   * as the event being that much older (or newer) than it is. Expiry is stamped
   * on arrival instead — see {@link TypingState.add}.
   */
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
   * Record an incoming typing event.
   *
   * Whether we are allowed to know who is typing is decided server-side — a name
   * only reaches us if we are entitled to it — so this just renders what arrived.
   * A hidden user we *are* permitted to see is labelled as such, so it's clear
   * they are invisible to everyone else.
   */
  add(data: TypingData): void {
    // extractText, not String(): an interpolated translation comes back as an
    // array of parts, which String() would join with commas.
    const name = data.displayName
      ? data.discloseOnline
        ? data.displayName
        : extractText(app.translator.trans('flarum-realtime.forum.typing-indicator.hidden-user', { username: data.displayName }))
      : extractText(app.translator.trans('flarum-realtime.forum.typing-indicator.anonymous-user'));

    // Our own clock, not `data.time`. A sender whose clock is slow would
    // otherwise have every event arrive already past EXPIRY_MS and be pruned on
    // the first read — the indicator would never light up — while a fast clock
    // would leave them typing forever. IndexTypingState stamps on arrival for
    // the same reason.
    this.usersTyping[name] = Date.now();
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
      this.truncationTimer = setTimeout(() => m.redraw(), latestTime + EXPIRY_MS - Date.now());
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
