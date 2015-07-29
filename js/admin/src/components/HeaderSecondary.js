import Component from 'flarum/Component';
import SessionDropdown from 'flarum/components/SessionDropdown';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `HeaderSecondary` component displays secondary header controls.
 */
export default class HeaderSecondary extends Component {
  view() {
    return (
      <ul className="Header-controls">
        {listItems(this.items().toArray())}
      </ul>
    );
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
