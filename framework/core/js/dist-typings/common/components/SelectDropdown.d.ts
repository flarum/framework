import Dropdown, { IDropdownAttrs } from './Dropdown';
import type Mithril from 'mithril';
export interface ISelectDropdownAttrs extends IDropdownAttrs {
    defaultLabel: string;
}
/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 */
export default class SelectDropdown<CustomAttrs extends ISelectDropdownAttrs = ISelectDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: ISelectDropdownAttrs): void;
    getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray;
}
