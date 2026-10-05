import Page, { type IPageAttrs } from 'flarum/common/components/Page';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
import type Sortable from 'sortablejs';
import DeckState from '../states/DeckState';
import type DeckColumnState from '../states/DeckColumnState';
export interface IDeckPageAttrs extends IPageAttrs {
}
export default class DeckPage<CustomAttrs extends IDeckPageAttrs = IDeckPageAttrs> extends Page<CustomAttrs> {
    protected deck: DeckState;
    protected observer: IntersectionObserver | null;
    protected sortable: typeof Sortable | null;
    protected chipSortable: Sortable | null;
    protected media: MediaQueryList[];
    /** Rows, by key, whose columns don't fit across the screen. */
    /** The column filling the screen in the phone layout. */
    protected activeIndex: number;
    /** Once the hero is gone the toolbar names the page instead. */
    protected heroDismissed: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    view(): JSX.Element;
    /** Both rows, with a divider to share the height once each has a column. */
    protected rows(): Mithril.Children[];
    protected row(key: string, row: number | null, columns: DeckColumnState[], phone: boolean, grow?: number): Mithril.Children;
    actionItems(): ItemList<Mithril.Children>;
    /** The deck-wide actions in the toolbar's menu. */
    optionItems(): ItemList<Mithril.Children>;
    protected addButton(): Mithril.Children;
    protected openAddColumn(): void;
    /**
     * A chip per column with its pending count: on phones the way to switch (and,
     * dragged, to reorder) columns; elsewhere a way to jump to columns scrolled
     * out of sight.
     */
    protected tabs(columns: DeckColumnState[], phone: boolean): Mithril.Children;
    /** On phones, chips are dragged to reorder (after a short press, so a tap still switches). */
    protected setUpChipSortable(): void;
    protected onLayoutChange: () => void;
    /** Core's phone breakpoint, read from its CSS variable so themes that move it are followed. */
    protected phoneQuery(): string;
    protected isPhone(): boolean;
    protected isShort(): boolean;
    protected scrollToColumn(id: string): void;
    protected observeColumns(): void;
    protected columnFor(element: HTMLElement): DeckColumnState | undefined;
}
