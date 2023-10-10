/// <reference types="mithril" />
import Component from '../Component';
import type { ComponentAttrs } from '../Component';
export interface IDetailedDropdownItemAttrs extends ComponentAttrs {
    /** The name of an icon to show in the dropdown item. */
    icon: string;
    /** The label of the dropdown item. */
    label: string;
    /** The description of the item. */
    description: string;
    /** An action to take when the item is clicked. */
    onclick: () => void;
    /** Whether the item is the current active/selected option. */
    active?: boolean;
}
export default class DetailedDropdownItem<CustomAttrs extends IDetailedDropdownItemAttrs = IDetailedDropdownItemAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
}
