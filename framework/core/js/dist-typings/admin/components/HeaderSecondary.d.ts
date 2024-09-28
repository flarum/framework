import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
/**
 * The `HeaderSecondary` component displays secondary header controls.
 */
export default class HeaderSecondary extends Component {
    view(): JSX.Element;
    /**
     * Build an item list for the controls.
     */
    items(): ItemList<Mithril.Children>;
}
