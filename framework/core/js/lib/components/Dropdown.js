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
    props.className = props.className || '';
    props.buttonClassName = props.buttonClassName || '';
    props.contentClassName = props.contentClassName || '';
    props.icon = props.icon || 'ellipsis-v';
    props.label = props.label || app.trans('controls');
  }

  view() {
    return (
      <div className={'dropdown btn-group ' + this.props.className}>
        {this.getButton()}
        <ul className={'dropdown-menu ' + this.props.menuClassName}>
          {listItems(this.props.children)}
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
      <a href="javascript:;"
        className={'dropdown-toggle ' + this.props.buttonClassName}
        data-toggle="dropdown"
        onclick={this.props.onclick}>
        {this.getButtonContent()}
      </a>
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
      icon(this.props.icon),
      <span className="label">{this.props.label}</span>,
      icon('caret-down', {className: 'caret'})
    ];
  }
}
