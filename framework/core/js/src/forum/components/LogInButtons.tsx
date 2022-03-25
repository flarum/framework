import type Mithril from 'mithril';
import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';

/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
export default class LogInButtons extends Component {
  view(): Mithril.Children {
    return <div className="LogInButtons">{this.items().toArray()}</div>;
  }

  /**
   * Build a list of LogInButton components.
   */
  items() {
    return new ItemList<Mithril.Children>();
  }
}
