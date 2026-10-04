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
    protected isRtl(): boolean;
    showNew(): void;
    protected body(): HTMLElement;
}
