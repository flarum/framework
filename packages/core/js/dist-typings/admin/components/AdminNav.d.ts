export default class AdminNav extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    query: Stream<string> | undefined;
    scrollToActive(): void;
    /**
     * Build an item list of main links to show in the admin navigation.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
    extensionItems(): ItemList<any>;
}
import Component from "../../common/Component";
import Stream from "../../common/utils/Stream";
import ItemList from "../../common/utils/ItemList";
