/**
 * The `HeaderSecondary` component displays secondary header controls.
 */
export default class HeaderSecondary extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * Build an item list for the controls.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
