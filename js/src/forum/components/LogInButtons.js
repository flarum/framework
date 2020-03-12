import Component from '../../common/Component';
import ItemList from '../../common/utils/ItemList';
import LogInButton from './LogInButton';

/**
 * The `LogInButtons` component displays a collection of social login buttons.
 */
export default class LogInButtons extends Component {
  view() {
    return <div className="LogInButtons">{this.items().toArray()}</div>;
  }

  /**
   * Build a list of LogInButton components.
   *
   * @return {ItemList}
   * @public
   */
  items() {
    const items = new ItemList();

    const drivers = app.forum.data.attributes.ssoDrivers;

    for (const driver in drivers) {
      const className = `Button LogInButton--${driver}`;
      const path = `/auth/${driver}`;
      var style = '';
      style += drivers[driver].buttonColor ? `background-color: ${drivers[driver].buttonColor};` : '';
      style += drivers[driver].buttonTextColor ? `color: ${drivers[driver].buttonTextColor}` : '';
      items.add(
        driver,
        <LogInButton className={className} icon={drivers[driver].icon ? drivers[driver].icon : ''} path={path}>
          {drivers[driver].buttonText ? drivers[driver].buttonText : driver}
        </LogInButton>
      );
    }

    return items;
  }
}
