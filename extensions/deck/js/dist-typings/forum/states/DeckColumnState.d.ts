import type { DeckColumnConfig, DeckColumnSource, DeckColumnType, DeckRealtimeEvent } from '../columns/DeckColumnType';
export default class DeckColumnState {
    config: DeckColumnConfig;
    readonly type: DeckColumnType;
    readonly source: DeckColumnSource;
    /** Items waiting behind the "new" pill. */
    newCount: number;
    /** Something may have changed while the column was off screen. */
    stale: boolean;
    visible: boolean;
    scrollTop: number;
    /** Items were just inserted above the reader; the component keeps their place. */
    protected insertedAtTop: boolean;
    protected checking: Promise<void> | null;
    protected checkTimer: number | null;
    /** Whether the queued check has to be a full one, not only `recheck()`. */
    protected fullCheckDue: boolean;
    /**
     * Checks and rechecks ask the server one at a time, so an answer that
     * arrives late can't undo a newer one: a discussion locked, then unlocked.
     */
    protected requests: Promise<void>;
    /** A check was asked for while one was running; it runs once that's done. */
    protected checkAgain: boolean;
    protected lastChecked: number;
    protected loaded: Promise<unknown> | null;
    constructor(config: DeckColumnConfig);
    title(): string;
    load(): Promise<unknown>;
    /**
     * Hands a realtime event to the source. Most are handled from the payload
     * alone; those only the server can place queue a check of this column.
     *
     * @param live Whether realtime is connected. Live columns take new items
     *             straight away; otherwise they wait behind the "new" pill.
     */
    handleRealtime(event: DeckRealtimeEvent, live: boolean): void;
    /**
     * However many events arrive, a column asks the server at most every MIN_CHECK_INTERVAL.
     *
     * @param recheckOnly Only `recheck()` is needed, unless something else in
     *                    the same burst asks for a full check.
     */
    queueCheck(live: boolean, recheckOnly?: boolean): void;
    cancelCheck(): void;
    /** See DeckColumnSource.prune(). */
    prune(): void;
    check(live: boolean): Promise<void>;
    /** Only asks again about the items the source noted; see DeckColumnSource.recheck(). */
    protected recheck(): Promise<void>;
    protected runRecheck(): Promise<void>;
    protected serially(request: () => Promise<void>): Promise<void>;
    consumeTopInsert(): boolean;
    showNew(): Promise<unknown>;
}
