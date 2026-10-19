import type User from 'flarum/common/models/User';

/** How long someone counts as typing after their last signal. */
export const TYPING_TIMEOUT_MS = 3500;

/** What a typing signal carries: who, and nothing else. */
export interface TypingSignal {
  userId: string;
}

/**
 * Who is typing in a conversation, from the signals its other members send.
 *
 * The realtime server relays a client event to a channel's other subscribers
 * as it is, and only the conversation's members can subscribe to its channel.
 * So the channel says who may be sending, and the signal names only the
 * sender's id, checked against the members here; nothing in it is taken on
 * trust. Names come from the store. Every member knows the others already,
 * so a member's online-status preference has no bearing: a one-to-one
 * conversation can't be anonymous.
 *
 * Expiry is judged on this clock, not the sender's.
 */
export default class TypingState {
  /** Member id => when they were last heard from, on this clock. */
  protected lastHeard = new Map<string, number>();

  constructor(protected participants: () => User[], protected self: () => string | null | undefined) {}

  static signal(userId: string): TypingSignal {
    return { userId };
  }

  /** Takes in a relayed signal. Returns whether it counted. */
  received(payload: unknown, now = Date.now()): boolean {
    const userId = TypingState.userIdOf(payload);

    if (!userId || userId === this.self()) return false;

    if (!this.participants().some((user) => user.id() === userId)) return false;

    this.lastHeard.set(userId, now);

    return true;
  }

  /** Members typing now, in the order they were first heard. */
  users(now = Date.now()): User[] {
    this.forgetSilent(now);

    const participants = this.participants();

    return [...this.lastHeard.keys()].map((id) => participants.find((user) => user.id() === id)).filter((user): user is User => !!user);
  }

  /** How long until the first member stops counting as typing, or null when nobody is. */
  msUntilNextExpiry(now = Date.now()): number | null {
    this.forgetSilent(now);

    const earliest = Math.min(...this.lastHeard.values());

    return Number.isFinite(earliest) ? Math.max(0, earliest + TYPING_TIMEOUT_MS - now) : null;
  }

  protected forgetSilent(now: number): void {
    for (const [id, heard] of this.lastHeard) {
      if (now - heard >= TYPING_TIMEOUT_MS) this.lastHeard.delete(id);
    }
  }

  protected static userIdOf(payload: unknown): string | null {
    if (!payload || typeof payload !== 'object' || !('userId' in payload)) return null;

    const id = (payload as { userId: unknown }).userId;

    return typeof id === 'string' || typeof id === 'number' ? String(id) : null;
  }
}

/**
 * Whether a change to the draft is worth telling the others about: it has to
 * have changed, and have something in it. A draft emptied by sending is not
 * someone typing, and announcing it put a phantom indicator right after every
 * message arrived.
 */
export function announces(previous: string, current: string): boolean {
  return current !== '' && current !== previous;
}
