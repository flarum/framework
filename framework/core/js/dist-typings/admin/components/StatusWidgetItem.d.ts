import Component, { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
export interface StatusWidgetItemAttrs extends ComponentAttrs {
    /**
     * The label/header for this status item
     */
    label: Mithril.Children;
    /**
     * The value/content to display
     */
    value: Mithril.Children;
    /**
     * Optional icon to display next to the label
     */
    icon?: string;
}
export default class StatusWidgetItem<CustomAttrs extends StatusWidgetItemAttrs = StatusWidgetItemAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    /**
     * Build the main content of the status item.
     * Can be overridden to completely customize the layout.
     */
    content(): Mithril.Children;
    /**
     * Build an ItemList of the status item's contents.
     * Extensions can easily add, remove, or modify parts.
     */
    contentItems(): ItemList<Mithril.Children>;
    /**
     * Render the icon (if provided).
     */
    iconView(): Mithril.Children;
    /**
     * Render the label.
     */
    labelView(): Mithril.Children;
    /**
     * Render the value.
     */
    valueView(): Mithril.Children;
}
