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
 * - `icon` The name of an icon to show in the dropdown toggle button.
 * - `caretIcon` The name of an icon to show on the right of the button.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 * - `onhide`
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
export default class Dropdown extends Component {
  static initProps(props) {
    super.initProps(props);

    props.className = props.className || '';
    props.buttonClassName = props.buttonClassName || '';
    props.menuClassName = props.menuClassName || '';
    props.label = props.label || app.trans('core.controls');
    props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'caret-down';
  }

  view() {
    const items = this.props.children ? listItems(this.props.children) : [];

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.props.className + ' itemCount' + items.length}>
        {this.getButton()}
        {this.getMenu(items)}
      </div>
    );
  }

  config(isInitialized) {
    if (isInitialized) return;

    // When opening the dropdown menu, work out if the menu goes beyond the
    // bottom of the viewport. If it does, we will apply class to make it show
    // above the toggle button instead of below it.
    this.$().on('shown.bs.dropdown', () => {
      const $menu = this.$('.Dropdown-menu').removeClass('Dropdown-menu--top');

      $menu.toggleClass(
        'Dropdown-menu--top',
        $menu.offset().top + $menu.height() > $(window).scrollTop() + $(window).height()
      );
    });

    this.$().on('hide.bs.dropdown', () => {
      if (this.props.onhide) {
        this.props.onhide();
        m.redraw();
      }
    });
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
      this.props.icon ? icon(this.props.icon, {className: 'Button-icon'}) : '',
      <span className="Button-label">{this.props.label}</span>,
      this.props.caretIcon ? icon(this.props.caretIcon, {className: 'Button-caret'}) : ''
    ];
  }

  getMenu(items) {
    return (
      <ul className={'Dropdown-menu dropdown-menu ' + this.props.menuClassName}>
        {items}
      </ul>
    );
  }
}
