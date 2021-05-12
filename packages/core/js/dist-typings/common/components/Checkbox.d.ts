/**
 * The `Checkbox` component defines a checkbox input.
 *
 * ### Attrs
 *
 * - `state` Whether or not the checkbox is checked.
 * - `className` The class name for the root element.
 * - `disabled` Whether or not the checkbox is disabled.
 * - `loading` Whether or not the checkbox is loading.
 * - `onchange` A callback to run when the checkbox is checked/unchecked.
 * - `children` A text label to display next to the checkbox.
 */
export default class Checkbox extends Component<import("../Component").ComponentAttrs> {
    constructor();
    /**
     * Get the template for the checkbox's display (tick/cross icon).
     *
     * @return {*}
     * @protected
     */
    protected getDisplay(): any;
    /**
     * Run a callback when the state of the checkbox is changed.
     *
     * @param {Boolean} checked
     * @protected
     */
    protected onchange(checked: boolean): void;
}
import Component from "../Component";
