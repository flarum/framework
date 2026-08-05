import Dropdown, { IDropdownAttrs } from './Dropdown';
import type Mithril from 'mithril';
export interface ISelectDropdownAttrs extends IDropdownAttrs {
    defaultLabel: Mithril.Children;
}
/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 */
export default class SelectDropdown<CustomAttrs extends ISelectDropdownAttrs = ISelectDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: ISelectDropdownAttrs): void;
    getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray;
    /**
     * Which option is in effect is otherwise conveyed only by a class on the item
     * and by the toggle button borrowing that option's label — neither of which a
     * screen reader announces while moving through the menu.
     *
     * `aria-current` rather than `aria-selected`: the latter is only valid on
     * roles this menu does not claim, and claiming them would mean implementing
     * the whole listbox keyboard contract. Unselected items are left without the
     * attribute rather than given `"false"`, since only one item can be current
     * and the absence says as much.
     */
    getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any>;
}
