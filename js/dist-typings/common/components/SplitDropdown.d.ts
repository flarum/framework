/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */
export default class SplitDropdown extends Dropdown {
    /**
     * Get the first child. If the first child is an array, the first item in that
     * array will be returned.
     *
     * @return {*}
     * @protected
     */
    protected getFirstChild(children: any): any;
}
import Dropdown from "./Dropdown";
