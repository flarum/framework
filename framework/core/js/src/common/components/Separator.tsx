import type Mithril from 'mithril';
import Component from '../Component';

/**
 * The `Separator` component defines a menu separator item.
 */
export default class Separator extends Component {
  static isListItem = true;

  view(): Mithril.Children {
    return <li className="Dropdown-separator" />;
  }
}
