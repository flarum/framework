import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import OverflowingList from '../../common/components/OverflowingList';
import type Mithril from 'mithril';

/**
 * The `HeaderPrimary` component displays primary header controls. On the
 * default skin, these are shown just to the right of the forum title.
 */
export default class HeaderPrimary extends Component {
  view(): JSX.Element {
    // Navigation is the part of the header that grows without bound —
    // extensions add links here, and a forum with a handful of them will not
    // fit beside the logo and the session controls on a narrow screen.
    // Whatever does not fit collapses into a menu instead of overlapping the
    // controls opposite.
    return <OverflowingList className="Header-controls" items={this.items().toArray()} />;
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
