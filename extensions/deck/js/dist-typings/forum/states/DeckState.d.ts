import DeckColumnState from './DeckColumnState';
import type { DeckColumnConfig, DeckColumnWidth } from '../columns/DeckColumnType';
/** The slice of a pusher-js channel this needs, without depending on flarum/realtime. */
interface Channel {
    bind_global(callback: (event: string, data: any) => void): void;
    unbind_global(callback: (event: string, data: any) => void): void;
}
type ChannelKind = 'public' | 'user';
/** Throttling for columns whose events need the server to decide; see DeckColumnState. */
export declare const MIN_CHECK_INTERVAL = 15000;
export declare const ROWS = 2;
/** Columns saved before rows existed, or with no row, belong to the first. */
export declare const rowOf: (config: DeckColumnConfig) => number;
/**
 * Held for the whole session, so leaving the deck to read a discussion and
 * coming back finds every column, its scroll position and its pending count
 * as they were.
 */
export default class DeckState {
    /** Every stored column, including ones that can't currently be shown. */
    protected configs: DeckColumnConfig[];
    protected states: Map<string, DeckColumnState>;
    protected channels: Array<[ChannelKind, Channel, (name: string, data: any) => void]>;
    protected recentEvents: Map<string, {
        kind: ChannelKind;
        at: number;
    }>;
    protected wasLive: boolean;
    protected heartbeat: number | null;
    protected lastCheck: number;
    protected saveTimer: number | null;
    /** Whether the member has a layout of their own, rather than the default. */
    protected customised: boolean;
    constructor();
    /**
     * Picks up a layout saved from outside the deck (e.g. "Add to Deck" on a
     * discussion), keeping the live state of columns that survived.
     */
    sync(): void;
    /** Every column that can be shown, top row first: the order phones swipe through. */
    columns(): DeckColumnState[];
    /** The columns in each row. The second row is empty until something is put there. */
    rows(): DeckColumnState[][];
    protected columnStates(): DeckColumnState[];
    canAddColumn(): boolean;
    addColumn(config: DeckColumnConfig): void;
    removeColumn(id: string): void;
    /**
     * Puts a column at `index` among the visible columns of `row`, as a drag and
     * drop leaves it. Hidden columns (an extension disabled, a permission
     * revoked) keep their place in the stored order and don't count.
     */
    moveTo(id: string, row: number, index: number): void;
    /**
     * Reorders within the single strip that phones and short screens show: the
     * column joins the row of the neighbour it lands after (or before, at the
     * very start), so both rows survive for when there's room for them again.
     */
    moveFlat(id: string, index: number): void;
    protected endOfRow(row: number): number;
    setWidth(id: string, width: DeckColumnWidth): void;
    isCustomised(): boolean;
    reset(): void;
    /** Batches rapid rearranging into one request. */
    protected save(): void;
    start(): void;
    stop(): void;
    /** Asks every column for anything new: on reconnecting, returning to the tab, or polling. */
    checkAll(): void;
    /** Channel objects outlive a dropped socket, so only a connected socket counts. */
    isLive(): boolean;
    /**
     * Every event on the public and the member's channel, handed to each column.
     * Realtime sends the member a payload built for them on their own channel,
     * and public discussions' events on the public channel too, so the first copy
     * is dispatched and a later personalised copy only refreshes the store.
     */
    protected onEvent(kind: ChannelKind, name: string, data: any): void;
    /** Only a type and id arrive: nothing to put in the store, just things to take out of columns. */
    protected onRemoved(data: any): void;
    protected pruneRecentEvents(now: number): void;
    protected onVisibilityChange: () => void;
    /**
     * While realtime is connected nothing polls: events drive every column. Events
     * can only be missed while disconnected, so reconnecting catches up once.
     * Without realtime, columns check on the admin's polling interval instead.
     */
    protected tick(): void;
    protected currentChannels(): Array<[ChannelKind, Channel]>;
    protected channelsChanged(): boolean;
    protected bindChannels(): void;
    protected unbindChannels(): void;
}
export {};
