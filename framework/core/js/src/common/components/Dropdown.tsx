import app from '../../common/app';
import Component, { ComponentAttrs } from '../Component';
import listItems, { ModdedChildrenWithItemName } from '../helpers/listItems';
import extractText from '../utils/extractText';
import type Mithril from 'mithril';
import Tooltip from './Tooltip';
import Icon from './Icon';

export interface IDropdownAttrs extends ComponentAttrs {
  /** A class name to apply to the dropdown toggle button. */
  buttonClassName?: string;
  /** Additional attributes to apply to the dropdown toggle button. */
  buttonAttrs?: Record<string, string>;
  /** A class name to apply to the dropdown menu. */
  menuClassName?: string;
  /** The name of an icon to show in the dropdown toggle button. */
  icon?: string;
  /** The name of an icon to show on the right of the button. */
  caretIcon?: string;
  /** The label of the dropdown toggle button. Defaults to 'Controls'. */
  label: Mithril.Children;
  /** The helper text to display under the button label. */
  helperText: Mithril.Children;
  /** The label used to describe the dropdown toggle button to assistive readers. Defaults to 'Toggle dropdown menu'. */
  accessibleToggleLabel?: string;
  /** An optional tooltip to show when hovering over the dropdown toggle button. */
  tooltip?: string;
  /** An action to take when the dropdown is collapsed. */
  onhide?: () => void;
  /** An action to take when the dropdown is opened. */
  onshow?: () => void;

  lazyDraw?: boolean;
}

/**
 * The `Dropdown` component displays a button which, when clicked, shows a
 * dropdown menu beneath it.
 *
 * The children will be displayed as a list inside the dropdown menu.
 */
export default class Dropdown<CustomAttrs extends IDropdownAttrs = IDropdownAttrs> extends Component<CustomAttrs> {
  protected showing = false;

  static initAttrs(attrs: IDropdownAttrs) {
    attrs.className ||= '';
    attrs.buttonClassName ||= '';
    attrs.menuClassName ||= '';
    attrs.label ||= '';
    attrs.caretIcon ??= 'fas fa-caret-down';
    attrs.accessibleToggleLabel ||= extractText(app.translator.trans('core.lib.dropdown.toggle_dropdown_accessible_label'));
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const items = vnode.children ? listItems(vnode.children as ModdedChildrenWithItemName[]) : [];
    const renderItems = this.attrs.lazyDraw ? this.showing : true;

    return (
      <div className={'ButtonGroup Dropdown dropdown ' + this.attrs.className + ' itemCount' + items.length + (this.showing ? ' open' : '')}>
        {this.getButton(vnode.children as Mithril.ChildArray)}
        {renderItems && this.getMenu(items)}
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);

    // When opening the dropdown menu, work out if the menu goes beyond the
    // bottom of the viewport. If it does, we will apply class to make it show
    // above the toggle button instead of below it.
    this.$().on('shown.bs.dropdown', () => {
      const { lazyDraw, onshow } = this.attrs;

      this.showing = true;

      // If using lazy drawing, redraw before calling `onshow` function
      // to make sure the menu DOM exists in case the callback tries to use it.
      if (lazyDraw) {
        m.redraw.sync();
      }

      if (typeof onshow === 'function') {
        onshow();
      }

      // If not using lazy drawing, keep previous functionality
      // of redrawing after calling onshow()
      if (!lazyDraw) {
        m.redraw();
      }

      const $menu = this.$('.Dropdown-menu');
      const isRight = $menu.hasClass('Dropdown-menu--right');

      const top = $menu.offset()?.top ?? 0;
      const height = $menu.height() ?? 0;
      const windowScrollTop = $(window).scrollTop() ?? 0;
      const windowHeight = $(window).height() ?? 0;

      $menu.removeClass('Dropdown-menu--top Dropdown-menu--right');

      $menu.toggleClass('Dropdown-menu--top', top + height > windowScrollTop + windowHeight);

      if (($menu.offset()?.top || 0) < 0) {
        $menu.removeClass('Dropdown-menu--top');
      }

      const left = $menu.offset()?.left ?? 0;
      const width = $menu.width() ?? 0;
      const windowScrollLeft = $(window).scrollLeft() ?? 0;
      const windowWidth = $(window).width() ?? 0;

      $menu.toggleClass('Dropdown-menu--right', isRight || left + width > windowScrollLeft + windowWidth);
    });

    this.$().on('hidden.bs.dropdown', () => {
      this.showing = false;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
    });

    this.$().on('focusout', (e: JQuery.FocusOutEvent) => {
      // Check if the new focused element is outside of this dropdown
      if (!this.$().has(e.relatedTarget as Element).length) {
        this.$().trigger('hidden.bs.dropdown');
      }
    });
    // Focusing out of the dropdown should close it.
  }

  /**
   * Get the template for the button.
   */
  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    let button = (
      <button
        className={'Dropdown-toggle ' + this.attrs.buttonClassName}
        aria-haspopup="menu"
        aria-label={this.attrs.accessibleToggleLabel}
        data-toggle="dropdown"
        onclick={this.attrs.onclick}
        {...this.attrs.buttonAttrs}
      >
        {this.getButtonContent(children)}
      </button>
    );

    if (this.attrs.tooltip) {
      button = (
        <Tooltip text={this.attrs.tooltip} position="bottom">
          {button}
        </Tooltip>
      );
    }

    return button;
  }

  /**
   * Get the template for the button's content.
   */
  getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray {
    return [
      this.attrs.icon ? <Icon name={this.attrs.icon} className="Button-icon" /> : '',
      <span className="Button-label">
        <span className="Button-labelText">{this.attrs.label}</span>
        {this.getButtonSubContent()}
      </span>,
      this.attrs.caretIcon ? <Icon name={this.attrs.caretIcon} className="Button-caret" /> : '',
    ];
  }

  protected getButtonSubContent(): Mithril.Children {
    return this.attrs.helperText ? <span className="Button-helperText">{this.attrs.helperText}</span> : null;
  }

  getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any> {
    return <ul className={'Dropdown-menu dropdown-menu ' + this.attrs.menuClassName}>{items}</ul>;
  }
}
