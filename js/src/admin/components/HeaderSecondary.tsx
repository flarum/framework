import Component from '../../common/Component';
import SessionDropdown from './SessionDropdown';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';

/**
 * The `HeaderSecondary` component displays secondary header controls.
 */
export default class HeaderSecondary extends Component {
    view() {
        return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
    }

    /**
     * Build an item list for the controls.
     *
     * @return {ItemList}
     */
    items() {
        const items = new ItemList();

        items.add('session', SessionDropdown.component());

        return items;
    }
}
