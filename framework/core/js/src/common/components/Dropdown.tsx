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

  protected backdropElement: HTMLDivElement | null = null;

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
    this.element.addEventListener('shown.bs.dropdown', () => {
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

      // Mithril doesn't really redraw this component sometimes (e.g. Discussion list)
      // Bootstrap 5 has removed the open class toggle and the backdrop
      // these need to be added manually.
      this.element.classList.add('open');
      this.backdropElement = document.createElement('div');
      this.backdropElement.classList.add('dropdown-backdrop');
      this.element.append(this.backdropElement);
    });

    this.element.addEventListener('hidden.bs.dropdown', () => {
      this.showing = false;

      if (this.attrs.onhide) {
        this.attrs.onhide();
      }

      m.redraw();
      this.element.classList.remove('open');
      this.backdropElement?.remove();
    });
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
        data-bs-toggle="dropdown"
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
      <span className="Button-label">{this.attrs.label}</span>,
      this.attrs.caretIcon ? <Icon name={this.attrs.caretIcon} className="Button-caret" /> : '',
    ];
  }

  getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any> {
    return <ul className={'Dropdown-menu dropdown-menu ' + this.attrs.menuClassName}>{items}</ul>;
  }
}
