import Dropdown, { IDropdownAttrs } from './Dropdown';
import Mithril from 'mithril';
export interface ISplitDropdownAttrs extends IDropdownAttrs {
    /** An optional main control button, which will be displayed instead of the first child. */
    mainAction?: Mithril.Vnode<any, any>;
}
/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button. Unless a custom
 * `mainAction` is provided as the main control.
 */
export default class SplitDropdown<CustomAttrs extends ISplitDropdownAttrs = ISplitDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: ISplitDropdownAttrs): void;
    getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any>;
    /**
     * Get the first child. If the first child is an array, the first item in that
     * array will be returned.
     */
    protected getFirstChild(children: Mithril.Children): Mithril.Vnode<any, any>;
}
