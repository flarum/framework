/**
 * The `SessionDropdown` component shows a button with the current user's
 * avatar/name, with a dropdown of session controls.
 */
export default class SessionDropdown extends Dropdown {
    /**
     * Build an item list for the contents of the dropdown menu.
     *
     * @return {ItemList<import('mithril').Children>}
     */
    items(): ItemList<import('mithril').Children>;
}
import Dropdown from "../../common/components/Dropdown";
import ItemList from "../../common/utils/ItemList";
