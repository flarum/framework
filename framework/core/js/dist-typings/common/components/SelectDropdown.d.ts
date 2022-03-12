/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 *
 * ### Attrs
 *
 * - `caretIcon`
 * - `defaultLabel`
 */
export default class SelectDropdown extends Dropdown {
    getButtonContent(children: any): JSX.Element[];
}
import Dropdown from "./Dropdown";
