import Dropdown, { IDropdownAttrs } from './Dropdown';
import Mithril from 'mithril';
export interface ISplitDropdownAttrs extends IDropdownAttrs {
}
/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */
export default class SplitDropdown extends Dropdown {
    static initAttrs(attrs: ISplitDropdownAttrs): void;
    getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any>;
    /**
     * Get the first child. If the first child is an array, the first item in that
     * array will be returned.
     */
    protected getFirstChild(children: Mithril.Children): Mithril.Vnode<any, any>;
}
