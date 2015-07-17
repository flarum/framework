import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `FooterPrimary` component displays primary footer controls, such as the
 * forum statistics. On the default skin, these are shown on the left side of
 * the footer.
 */
export default class FooterPrimary extends Component {
  view() {
    return (
      <ul className="Footer-controls">
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

    // TODO: add forum statistics

    return items;
  }
}
