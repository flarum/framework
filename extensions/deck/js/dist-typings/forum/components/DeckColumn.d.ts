import Component, { type ComponentAttrs } from 'flarum/common/Component';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type DeckState from '../states/DeckState';
import type DeckColumnState from '../states/DeckColumnState';
export interface IDeckColumnAttrs extends ComponentAttrs {
    deck: DeckState;
    column: DeckColumnState;
    /** Shown in the single strip phones and short screens get, rather than in a row. */
    flat?: boolean;
}
export default class DeckColumn<CustomAttrs extends IDeckColumnAttrs = IDeckColumnAttrs> extends Component<CustomAttrs> {
    protected previousHeight: number;
    /** While the edge is being dragged: where it started, and what the column is sized against. */
    protected resizing: {
        startX: number;
        startShown: number;
        others: number;
        row: number;
    } | null;
    protected previousTop: number;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    controlItems(): ItemList<Mithril.Children>;
    menuItems(): ItemList<Mithril.Children>;
    /**
     * Moves the column from its menu, then says where it went and gives focus
     * back to its menu, which the move took out of the page and put back.
     */
    protected move(callback: () => void): void;
    /** The column's edge: drag it, or focus it and use the arrow keys, to resize. */
    protected resizeHandle(): Mithril.Children;
    protected startResize(e: PointerEvent): void;
    protected resize(e: PointerEvent & {
        redraw?: boolean;
    }): void;
    protected endResize(): void;
    protected resizeByKey(e: KeyboardEvent & {
        redraw?: boolean;
    }): void;
    /**
     * A row scrolls sideways, so it clips anything that overflows it, and a
     * column's menu would be cut off at the bottom of its row. Pinning it to the
     * window instead lets it overlap the row below, opening upwards when there's
     * more room there and scrolling when there's room for neither.
     */
    protected pinMenu(): void;
    protected unpinMenu(): void;
    protected menu(): HTMLElement | null;
    /** A pinned menu would stay put while the deck moved under it. */
    protected onScrollWhilePinned: (e: Event) => void;
    protected closeMenu: () => void;
    protected isRtl(): boolean;
    showNew(): void;
    protected body(): HTMLElement;
}
