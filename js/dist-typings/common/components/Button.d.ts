/**
 * The `Button` component defines an element which, when clicked, performs an
 * action.
 *
 * ### Attrs
 *
 * - `icon` The name of the icon class. If specified, the button will be given a
 *   'has-icon' class name.
 * - `disabled` Whether or not the button is disabled. If truthy, the button
 *   will be given a 'disabled' class name, and any `onclick` handler will be
 *   removed.
 * - `loading` Whether or not the button should be in a disabled loading state.
 *
 * All other attrs will be assigned as attributes on the button element.
 *
 * Note that a Button has no default class names. This is because a Button can
 * be used to represent any generic clickable control, like a menu item.
 */
export default class Button extends Component<import("../Component").ComponentAttrs> {
    constructor();
    /**
     * Get the template for the button's content.
     *
     * @return {*}
     * @protected
     */
    protected getButtonContent(children: any): any;
}
import Component from "../Component";
