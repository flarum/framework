/** Column widths in pixels, as the server keeps them (DeckLayout). */
export declare const MIN_WIDTH = 240;
export declare const MAX_WIDTH = 900;
export declare const DEFAULT_WIDTH = 280;
/** The top row's share of the deck's height, as the server keeps it. */
export declare const MIN_SPLIT = 0.2;
export declare const MAX_SPLIT = 0.8;
export declare const DEFAULT_SPLIT = 0.5;
export declare const clampWidth: (width: number) => number;
export declare const clampSplit: (split: number) => number;
/**
 * The width to save so a column shows `shown` pixels wide.
 *
 * Columns grow in proportion to their widths to fill a row with room to spare,
 * so there a saved width is a share rather than a size: each column shows
 * `width * row / total`. Only once the row is full do they show exactly their
 * widths (and the row scrolls).
 *
 * @param others The saved widths of the other columns in the row, added up.
 * @param row The width the row has for its columns.
 */
export declare function widthToShow(shown: number, others: number, row: number): number;
