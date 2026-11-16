import Component, { type ComponentAttrs } from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface IHeaderSecondaryAttrs extends ComponentAttrs {
}
/**
 * The `HeaderSecondary` component displays secondary header controls, such as
 * the search box and the user menu. On the default skin, these are shown on the
 * right side of the header.
 */
export default class HeaderSecondary<CustomAttrs extends IHeaderSecondaryAttrs = IHeaderSecondaryAttrs> extends Component<CustomAttrs> {
    view(): JSX.Element;
    items(): ItemList<Mithril.Children>;
    /**
     * Switch the forum to the given locale.
     */
    changeLocale(locale: string): void;
}
