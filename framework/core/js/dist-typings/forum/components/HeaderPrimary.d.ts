/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    /**
     * Build an item list for the controls.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
