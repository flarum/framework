/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */
export default class SplitDropdown extends Dropdown {
    /**
     * Get the first child. If the first child is an array, the first item in that
     * array will be returned.
     *
     * @param {unknown[] | unknown} children
     * @return {unknown}
     * @protected
     */
    protected getFirstChild(children: unknown[] | unknown): unknown;
}
import Dropdown from "./Dropdown";
