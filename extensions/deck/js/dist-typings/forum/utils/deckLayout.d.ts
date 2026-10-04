import type { DeckColumnConfig, DeckColumnWidth } from '../columns/DeckColumnType';
export declare const PREFERENCE_KEY = "deckColumns";
export declare function canUseDeck(): boolean;
export declare function maxColumns(): number;
export declare function newColumnId(): string;
export declare function makeColumn(type: string, params?: DeckColumnConfig['params'], width?: DeckColumnWidth): DeckColumnConfig;
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
