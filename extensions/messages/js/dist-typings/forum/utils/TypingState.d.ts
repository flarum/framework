import type User from 'flarum/common/models/User';
/** How long someone counts as typing after their last signal. */
export declare const TYPING_TIMEOUT_MS = 3500;
/** What a typing signal carries: who, and nothing else. */
export interface TypingSignal {
    userId: string;
}
/** Tracks server-authorized typing signals, expiring them on this clock. */
export default class TypingState {
    protected participants: () => User[];
    protected self: () => string | null | undefined;
    protected resolveParticipant?: ((id: string) => User | undefined) | undefined;
    /** Member id => when they were last heard from, on this clock. */
    protected lastHeard: Map<string, number>;
    protected resolved: Map<string, User>;
    constructor(participants: () => User[], self: () => string | null | undefined, resolveParticipant?: ((id: string) => User | undefined) | undefined);
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
