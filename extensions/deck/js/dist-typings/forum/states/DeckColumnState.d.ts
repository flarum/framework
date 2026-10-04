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
    /** However many events arrive, a column asks the server at most every MIN_CHECK_INTERVAL. */
    queueCheck(live: boolean): void;
    cancelCheck(): void;
    check(live: boolean): Promise<void>;
    consumeTopInsert(): boolean;
    showNew(): Promise<unknown>;
}
