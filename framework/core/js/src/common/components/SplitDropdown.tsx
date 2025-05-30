import Dropdown, { IDropdownAttrs } from './Dropdown';
import Button from './Button';
import Mithril from 'mithril';
import classList from '../utils/classList';
import Tooltip from './Tooltip';
import Icon from './Icon';

export interface ISplitDropdownAttrs extends IDropdownAttrs {
  /** An optional main control button, which will be displayed instead of the first child. */
  mainAction?: Mithril.Vnode<any, any>;
}

/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button. Unless a custom
 * `mainAction` is provided as the main control.
 */
export default class SplitDropdown<CustomAttrs extends ISplitDropdownAttrs = ISplitDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISplitDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Dropdown--split', { 'Dropdown--withMainAction': attrs.mainAction });
    attrs.menuClassName = classList(attrs.menuClassName, 'Dropdown-menu--right');
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    // Make a copy of the attrs of the first child component. We will assign
    // these attrs to a new button, so that it has exactly the same behaviour as
    // the first child.
    const firstChild = this.attrs.mainAction || this.getFirstChild(children);
    const buttonAttrs = Object.assign({}, firstChild?.attrs);
    buttonAttrs.className = classList(buttonAttrs.className, 'SplitDropdown-button Button', this.attrs.buttonClassName);

    let button = <Button {...buttonAttrs}>{firstChild.children}</Button>;

    if (this.attrs.tooltip) {
      button = (
        <Tooltip text={this.attrs.tooltip} position="bottom">
          {button}
        </Tooltip>
      );
    }

    return (
      <>
        {button}
        <button
          type="button"
          className={'Dropdown-toggle Button Button--icon ' + this.attrs.buttonClassName}
          aria-haspopup="menu"
          aria-label={this.attrs.accessibleToggleLabel}
          data-toggle="dropdown"
        >
          {this.attrs.icon ? <Icon name={this.attrs.icon} className="Button-icon" /> : null}
          <Icon name="fas fa-caret-down" className="Button-caret" />
        </button>
      </>
    );
  }

  /**
   * Get the first child. If the first child is an array, the first item in that
   * array will be returned.
   */
  protected getFirstChild(children: Mithril.Children): Mithril.Vnode<any, any> {
    let firstChild = children;

    while (firstChild instanceof Array) firstChild = firstChild[0];

    return firstChild as Mithril.Vnode<any, any>;
  }
}
