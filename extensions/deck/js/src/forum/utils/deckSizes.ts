/** Column widths in pixels, as the server keeps them (DeckLayout). */
export const MIN_WIDTH = 240;
export const MAX_WIDTH = 900;
export const DEFAULT_WIDTH = 280;

/** The top row's share of the deck's height, as the server keeps it. */
export const MIN_SPLIT = 0.2;
export const MAX_SPLIT = 0.8;
export const DEFAULT_SPLIT = 0.5;

export const clampWidth = (width: number): number => Math.round(Math.min(MAX_WIDTH, Math.max(MIN_WIDTH, width)));

export const clampSplit = (split: number): number => Math.round(Math.min(MAX_SPLIT, Math.max(MIN_SPLIT, split)) * 1000) / 1000;

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
export function widthToShow(shown: number, others: number, row: number): number {
  if (shown + others >= row) return clampWidth(shown);

  return clampWidth((shown * others) / (row - shown));
}
