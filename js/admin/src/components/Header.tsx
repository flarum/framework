import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import SessionDropdown from './SessionDropdown';

export default class Header extends Component {
  /**
   * @inheritdoc
   */
  view() {
    return this.items().toVnodes();
  }

  /**
   * Build an item list for the header contents.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('session', <SessionDropdown/>);

    return items;
  }
}
