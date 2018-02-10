import Component from 'Component';
import ItemList from 'utils/ItemList';

/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
export default class LogInButtons extends Component {
  view() {
    return (
      <div className="LogInButtons">
        {this.items().toArray()}
      </div>
    );
  }

  /**
   * Build a list of LogInButton components.
   *
   * @return {ItemList}
   * @public
   */
  items() {
    return new ItemList();
  }
}
