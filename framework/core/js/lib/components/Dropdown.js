import Component from 'flarum/Component';
import icon from 'flarum/helpers/icon';
import listItems from 'flarum/helpers/listItems';

/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * ### Props
 *
 * - `buttonClassName` A class name to apply to the dropdown toggle button.
 * - `menuClassName` A class name to apply to the dropdown menu.
 * - `icon` The name of an icon to show in the dropdown toggle button. Defaults
 *   to 'ellipsis-v'.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
export default class Dropdown extends Component {
  static initProps(props) {
    super.initProps(props);

    props.className = props.className || '';
    props.buttonClassName = props.buttonClassName || '';
    props.contentClassName = props.contentClassName || '';
    props.icon = props.icon || 'ellipsis-v';
    props.label = props.label || app.trans('core.controls');
  }

  view() {
    const items = listItems(this.props.children);

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.props.className + ' itemCount' + items.length}>
        {this.getButton()}
        <ul className={'Dropdown-menu dropdown-menu ' + this.props.menuClassName}>
          {items}
        </ul>
      </div>
    );
  }

  /**
   * Get the template for the button.
   *
   * @return {*}
   * @protected
   */
  getButton() {
    return (
      <button
        className={'Dropdown-toggle ' + this.props.buttonClassName}
        data-toggle="dropdown"
        onclick={this.props.onclick}>
        {this.getButtonContent()}
      </button>
    );
  }

  /**
   * Get the template for the button's content.
   *
   * @return {*}
   * @protected
   */
  getButtonContent() {
    return [
      icon(this.props.icon, {className: 'Button-icon'}),
      <span className="Button-label">{this.props.label}</span>, ' ',
      icon('caret-down', {className: 'Button-caret'})
    ];
  }
}
