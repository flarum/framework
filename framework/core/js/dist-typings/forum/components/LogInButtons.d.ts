/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
export default class LogInButtons extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    view(): JSX.Element;
    /**
     * Build a list of LogInButton components.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
}
import Component from "../../common/Component";
import ItemList from "../../common/utils/ItemList";
