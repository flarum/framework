import Dropdown, { IDropdownAttrs } from '../../common/components/Dropdown';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface ISessionDropdownAttrs extends IDropdownAttrs {
}
/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown<CustomAttrs extends ISessionDropdownAttrs = ISessionDropdownAttrs> extends Dropdown<CustomAttrs> {
    static initAttrs(attrs: ISessionDropdownAttrs): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    getButtonContent(): (string | JSX.Element)[];
    /**
     * Build an item list for the contents of the dropdown menu.
     */
    items(): ItemList<Mithril.Children>;
}
