import IndexSidebar, { type IndexSidebarAttrs } from 'flarum/forum/components/IndexSidebar';
import Mithril from 'mithril';
import ItemList from 'flarum/common/utils/ItemList';
export interface IMessagesSidebarAttrs extends IndexSidebarAttrs {
}
export default class MessagesSidebar<CustomAttrs extends IMessagesSidebarAttrs = IMessagesSidebarAttrs> extends IndexSidebar<CustomAttrs> {
    static initAttrs(attrs: IMessagesSidebarAttrs): void;
    items(): ItemList<Mithril.Children>;
    /**
     * Open the composer for a new message.
     */
    newMessageAction(): Promise<unknown>;
}
