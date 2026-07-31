import type Mithril from 'mithril';
import Component from '../Component';
import type { ComponentAttrs } from '../Component';
export interface IDetailedDropdownItemAttrs extends ComponentAttrs {
    /**
     * The name of an icon to show in the dropdown item, or a rendered icon —
     * which lets the caller pass attributes of its own, e.g. `noStyleOverride`
     * for an icon whose style carries meaning.
     */
    icon: string | Mithril.Children;
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
