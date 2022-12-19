import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component {
    view(): JSX.Element;
    config(context: any): void;
    /**
     * Build an item list for the controls.
     */
    items(): ItemList<Mithril.Children>;
}
