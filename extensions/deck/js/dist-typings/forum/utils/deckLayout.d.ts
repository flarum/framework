import type { DeckColumnConfig } from '../columns/DeckColumnType';
export declare const PREFERENCE_KEY = "deckColumns";
export declare const SPLIT_PREFERENCE_KEY = "deckRowSplit";
export declare function canUseDeck(): boolean;
export declare function maxColumns(): number;
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
