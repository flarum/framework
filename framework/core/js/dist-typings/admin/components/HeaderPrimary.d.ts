/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component<import("../../common/Component").ComponentAttrs> {
    constructor();
    config(isInitialized: any, context: any): void;
    /**
     * Build an item list for the controls.
     *
     * @return {ItemList}
     */
    items(): ItemList;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
