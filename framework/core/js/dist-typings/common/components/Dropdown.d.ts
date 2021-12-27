/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * ### Attrs
 *
 * - `buttonClassName` A class name to apply to the dropdown toggle button.
 * - `menuClassName` A class name to apply to the dropdown menu.
 * - `icon` The name of an icon to show in the dropdown toggle button.
 * - `caretIcon` The name of an icon to show on the right of the button.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 * - `accessibleToggleLabel` The label used to describe the dropdown toggle button to assistive readers. Defaults to 'Toggle dropdown menu'.
 * - `onhide`
 * - `onshow`
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
export default class Dropdown extends Component<import("../Component").ComponentAttrs, undefined> {
    static initAttrs(attrs: any): void;
    constructor();
    showing: boolean | undefined;
    /**
     * Get the template for the button.
     *
     * @return {import('mithril').Children}
     * @protected
     */
    protected getButton(children: any): import('mithril').Children;
    /**
     * Get the template for the button's content.
     *
     * @return {import('mithril').Children}
     * @protected
     */
    protected getButtonContent(children: any): import('mithril').Children;
    getMenu(items: any): JSX.Element;
}
import Component from "../Component";
