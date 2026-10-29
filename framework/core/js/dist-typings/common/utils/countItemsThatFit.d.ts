/**
 * Work out how many items from the start of a row can be shown before the rest
 * have to be collapsed into an overflow menu.
 *
 * Kept separate from the component that uses it so the decision can be
 * exercised directly: it is pure arithmetic over measured widths, and the
 * awkward cases — a boundary where an item exactly fits, a row that has to make
 * room for the toggle it is about to show — are the ones most worth pinning
 * down in tests.
 *
 * The result depends only on the arguments. Nothing here may read from the row
 * being laid out: its width is a *consequence* of the last decision, so feeding
 * it back in produces a layout that oscillates rather than settles.
 *
 * @param itemWidths Width of each item, in order, as measured while they were
 *                   all on the row.
 * @param available  Room the row has to lay items out in.
 * @param toggleWidth Width of the overflow toggle, needed whenever at least one
 *                    item is collapsed.
 * @returns How many leading items to show. Everything after them belongs in the
 *          overflow menu.
 */
export default function countItemsThatFit(itemWidths: number[], available: number, toggleWidth: number): number;
