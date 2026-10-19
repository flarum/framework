import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export default class Footer extends Component {
  view() {
    const items = this.items().toArray();

    // Nothing has been added, so nothing is rendered -- an empty container
    // would still take up its own padding at the bottom of every page.
    if (!items.length) return null;

    return <div className="Footer-container container">{items}</div>;
  }

  items(): ItemList<Mithril.Children> {
    return new ItemList();
  }
}
