export default class DashboardWidget extends Component<import("../../common/Component").ComponentAttrs, undefined> {
    constructor();
    /**
     * Get the class name to apply to the widget.
     *
     * @return {string}
     */
    className(): string;
    /**
     * Get the content of the widget.
     *
     * @return {import('mithril').Children}
     */
    content(): import('mithril').Children;
}
import Component from "../../common/Component";
