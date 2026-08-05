import Component, { ComponentAttrs } from '../Component';
import { ModdedChildrenWithItemName } from '../helpers/listItems';
import type Mithril from 'mithril';
export interface IOverflowingListAttrs extends ComponentAttrs {
    /** The items to lay out, as returned by `ItemList.toArray()`. */
    items: ModdedChildrenWithItemName[];
    /** A class name to apply to the list element. */
    className?: string;
    /** The label used to describe the overflow menu to assistive readers. */
    accessibleToggleLabel?: string;
}
/**
 * Lays out a list of items on a single row, moving those that do not fit into
 * a dropdown at the end of the row.
 *
 * The list is rendered in full and measured after each paint, so item widths
 * come from the real laid-out DOM rather than an estimate. That keeps the
 * result correct whatever an item happens to contain — an icon, a long label,
 * a translated string, or something an extension added.
 */
export default class OverflowingList extends Component<IOverflowingListAttrs> {
    /**
     * How many leading items are shown in the row. `null` means "not measured
     * yet", during which everything renders so the widths can be read.
     */
    protected visibleCount: number | null;
    /**
     * Width of each item, by index, captured while all of them were on the row.
     * Measuring once and reusing avoids feeding the widths of an already
     * collapsed row back into the next calculation.
     */
    protected itemWidths: number[];
    protected toggleWidth: number;
    protected observer?: ResizeObserver;
    protected onWindowResize?: () => void;
    view(vnode: Mithril.Vnode<IOverflowingListAttrs, this>): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<IOverflowingListAttrs, this>): void;
    /**
     * Work out how many items fit, and redraw if that has changed.
     */
    protected recalculate(): void;
    /**
     * How much room the row could occupy, rather than how much it currently
     * does.
     *
     * The list sits in a flex parent that shrink-wraps its contents, so once
     * items have been collapsed the element reports the narrower width it took
     * *because* of the collapse. Measuring that would be circular — the row
     * could never grow back. What matters is the gap to whatever sits beside it.
     */
    protected availableWidth(list: HTMLElement): number;
    protected outerWidth(el: HTMLElement): number;
}
