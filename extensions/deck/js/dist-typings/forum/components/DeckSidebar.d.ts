import IndexSidebar, { type IndexSidebarAttrs } from 'flarum/forum/components/IndexSidebar';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';
export interface IDeckSidebarAttrs extends IndexSidebarAttrs {
    canAddColumn: boolean;
    onaddcolumn: () => void;
}
/**
 * The forum's side nav, with Deck's own primary action in place of starting a
 * discussion, as the Messages page does with sending a message.
 */
export default class DeckSidebar<CustomAttrs extends IDeckSidebarAttrs = IDeckSidebarAttrs> extends IndexSidebar<CustomAttrs> {
    static initAttrs(attrs: IDeckSidebarAttrs): void;
    items(): ItemList<Mithril.Children>;
}
