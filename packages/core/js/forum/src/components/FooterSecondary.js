import Component from 'flarum/Component';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `FooterSecondary` component displays secondary footer controls, such as
 * the 'Powered by Flarum' message. On the default skin, these are shown on the
 * right side of the footer.
 */
export default class FooterSecondary extends Component {
  view() {
    return (
      <ul className="footer-controls">
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

    items.add('poweredBy', (
      <a href="http://flarum.org?r=forum" target="_blank">
        Powered by Flarum
      </a>
    ));

    return items;
  }
}
