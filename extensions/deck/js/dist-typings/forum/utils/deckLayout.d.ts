import type { DeckColumnConfig } from '../columns/DeckColumnType';
export declare const PREFERENCE_KEY = "deckColumns";
export declare const SPLIT_PREFERENCE_KEY = "deckRowSplit";
export declare function canUseDeck(): boolean;
export declare function maxColumns(): number;
/** As the server keeps it: a column's name is cut to this length. */
export declare const MAX_NAME_LENGTH = 60;
/**
 * Whether two columns' settings are the same. The server sends no settings as
 * an empty list rather than an empty object, so they're compared by content.
 */
export declare function sameParams(a: DeckColumnConfig['params'], b: DeckColumnConfig['params']): boolean;
export declare function newColumnId(): string;
export declare function makeColumn(type: string, params?: DeckColumnConfig['params'], width?: number): DeckColumnConfig;
/**
 * The member's saved layout, or a starter deck if they've never saved one.
 * Columns whose type is unknown or unavailable (an extension was disabled, a
 * permission was revoked) are hidden but kept, so they come back if it does.
 */
export declare function storedColumns(): DeckColumnConfig[];
export declare function hasCustomLayout(): boolean;
export declare function defaultColumns(): DeckColumnConfig[];
export declare function isDisplayable(column: DeckColumnConfig): boolean;
/** Null clears the layout, so the member follows the default (and any later change to it). */
export declare function saveColumns(columns: DeckColumnConfig[] | null): Promise<unknown>;
/** The top row's share of the height; null for an even split. */
export declare function storedSplit(): number | null;
export declare function saveSplit(split: number | null): Promise<unknown>;
/** Back to the default deck, rows evenly split. */
export declare function resetLayout(): Promise<unknown>;
