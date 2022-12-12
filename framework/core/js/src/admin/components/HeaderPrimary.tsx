import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import listItems from '../../common/helpers/listItems';
import type Mithril from 'mithril';

/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component{
  
  view() {
    return <ul className="Header-controls">{listItems(this.items().toArray())}</ul>;
  }

  config(context:any) {
    // Since this component is 'above' the content of the page (that is, it is a
    // part of the global UI that persists between routes), we will flag the DOM
    // to be retained across route changes.
    context.retain = true;
  }

  /**
   * Build an item list for the controls.
   */
  items() {
    const items = new ItemList<Mithril.Children>();
    return items;
  }
}
