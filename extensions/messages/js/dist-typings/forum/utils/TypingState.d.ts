import type User from 'flarum/common/models/User';
/** How long someone counts as typing after their last signal. */
export declare const TYPING_TIMEOUT_MS = 3500;
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
    protected participants: () => User[];
    protected self: () => string | null | undefined;
    /** Member id => when they were last heard from, on this clock. */
    protected lastHeard: Map<string, number>;
    constructor(participants: () => User[], self: () => string | null | undefined);
    static signal(userId: string): TypingSignal;
    /** Takes in a relayed signal. Returns whether it counted. */
    received(payload: unknown, now?: number): boolean;
    /** Members typing now, in the order they were first heard. */
    users(now?: number): User[];
    /** How long until the first member stops counting as typing, or null when nobody is. */
    msUntilNextExpiry(now?: number): number | null;
    protected forgetSilent(now: number): void;
    protected static userIdOf(payload: unknown): string | null;
}
/**
 * Whether a change to the draft is worth telling the others about: it has to
 * have changed, and have something in it. A draft emptied by sending is not
 * someone typing, and announcing it put a phantom indicator right after every
 * message arrived.
 */
export declare function announces(previous: string, current: string): boolean;
