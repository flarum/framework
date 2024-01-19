import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface IndexSidebarAttrs extends ComponentAttrs {
}
export default class IndexSidebar<CustomAttrs extends IndexSidebarAttrs = IndexSidebarAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    /**
     * Build an item list for the sidebar of the index page. By default this is a
     * "New Discussion" button, and then a DropdownSelect component containing a
     * list of navigation items.
     */
    items(): ItemList<Mithril.Children>;
    /**
     * Build an item list for the navigation in the sidebar of the index page. By
     * default this is just the 'All Discussions' link.
     */
    navItems(): ItemList<Mithril.Children>;
    /**
     * Open the composer for a new discussion or prompt the user to login.
     */
    newDiscussionAction(): Promise<unknown>;
}
