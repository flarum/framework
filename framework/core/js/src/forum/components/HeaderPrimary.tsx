import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import type Mithril from 'mithril';

/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component {
  view(): JSX.Element {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  /**
   * Build an item list for the controls.
   *
   * @return {ItemList<import('mithril').Children>}
   */

  items(): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }
}
