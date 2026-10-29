import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type Sortable from 'sortablejs';
import type DeckState from '../states/DeckState';
import type DeckColumnState from '../states/DeckColumnState';
export interface IDeckRowAttrs extends ComponentAttrs {
    deck: DeckState;
    columns: DeckColumnState[];
    /** The row's share of the deck's height, with two rows. */
    grow?: number;
    /** Which row this is, or null for the single strip phones and short screens get. */
    row: number | null;
    /** SortableJS, once its chunk has loaded; null until then, or to turn dragging off. */
    sortable: typeof Sortable | null;
    scrollLeft: number;
    onscrolled: (scrollLeft: number) => void;
    ondrag: (dragging: boolean) => void;
    /** Phone only: the index of the column filling the screen. */
    onactive?: (index: number) => void;
}
/**
 * One horizontally scrolling strip of columns. It shows which way there's
 * more (edge fades and scroll buttons), and lets columns be dragged within it
 * and to the other row.
 */
export default class DeckRow<CustomAttrs extends IDeckRowAttrs = IDeckRowAttrs> extends Component<CustomAttrs> {
    protected sortableInstance: Sortable | null;
    protected resizeObserver: ResizeObserver | null;
    protected active: number;
    view(): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    protected container(): HTMLElement;
    protected scroll(direction: -1 | 1): void;
    protected onscroll(e: Event & {
        redraw?: boolean;
    }): void;
    /**
     * Toggled on the element directly: this runs on every scroll frame, and
     * nothing else on the page depends on it.
     */
    protected updateScrollState(): void;
    protected setUpSortable(): void;
}
