import Component from '../Component';
import icon from '../helpers/icon';
import listItems from '../helpers/listItems';

/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * ### Attrs
 *
 * - `buttonClassName` A class name to apply to the dropdown toggle button.
 * - `menuClassName` A class name to apply to the dropdown menu.
 * - `icon` The name of an icon to show in the dropdown toggle button.
 * - `caretIcon` The name of an icon to show on the right of the button.
 * - `label` The label of the dropdown toggle button. Defaults to 'Controls'.
 * - `onhide`
 * - `onshow`
 *
 * The children will be displayed as a list inside of the dropdown menu.
 */
export default class Dropdown extends Component {
  oninit(vnode) {
    this.showing = false;
  }

  view(vnode) {
    const items = vnode.children ? listItems(vnode.children) : [];

    this.initAttrs(vnode.attrs);

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + vnode.attrs.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
        {this.getButton(vnode.attrs, vnode.children)}
        {this.getMenu(vnode.attrs.menuClassName, items)}
      </div>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    // When opening the dropdown menu, work out if the menu goes beyond the
    // bottom of the viewport. If it does, we will apply class to make it show
    // above the toggle button instead of below it.
    this.$().on('shown.bs.dropdown', () => {
      this.showing = true;

      if (vnode.attrs.onshow) {
        vnode.attrs.onshow();
      }

      m.redraw();

      const $menu = this.$('.Dropdown-menu');
      const isRight = $menu.hasClass('Dropdown-menu--right');

      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

      $menu.toggleClass('Dropdown-menu--top', $menu.offset().top + $menu.height() > $(window).scrollTop() + $(window).height());

      if ($menu.offset().top < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      $menu.toggleClass('Dropdown-menu--right', isRight || $menu.offset().left + $menu.width() > $(window).scrollLeft() + $(window).width());
    });

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;

      if (vnode.attrs.onhide) {
        vnode.attrs.onhide();
      }

      m.redraw();
    });
  }

  initAttrs(attrs) {
    attrs.className = attrs.className || '';
    attrs.buttonClassName = attrs.buttonClassName || '';
    attrs.menuClassName = attrs.menuClassName || '';
    attrs.label = attrs.label || '';
    attrs.caretIcon = typeof attrs.caretIcon !== 'undefined' ? attrs.caretIcon : 'fas fa-caret-down';
  }

  /**
   * Get the template for the button.
   *
   * @return {*}
   * @protected
   */
  getButton(attrs, children) {
    return (
      <button className={'Dropdown-toggle ' + attrs.buttonClassName} data-toggle="dropdown" onclick={attrs.onclick}>
        {this.getButtonContent(attrs, children)}
      </button>
    );
  }

  /**
   * Get the template for the button's content.
   *
   * @return {*}
   * @protected
   */
  getButtonContent(attrs, children) {
    return [
      attrs.icon ? icon(attrs.icon, { className: 'Button-icon' }) : '',
      <span className="Button-label">{attrs.label}</span>,
      attrs.caretIcon ? icon(attrs.caretIcon, { className: 'Button-caret' }) : '',
    ];
  }

  getMenu(menuClassName, items) {
    return <ul className={'Dropdown-menu dropdown-menu ' + menuClassName}>{items}</ul>;
  }
}
