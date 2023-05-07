import Dropdown, { IDropdownAttrs } from './Dropdown';
import Button from './Button';
import icon from '../helpers/icon';
import Mithril from 'mithril';
import classList from '../utils/classList';

export interface ISplitDropdownAttrs extends IDropdownAttrs {}

/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */
export default class SplitDropdown extends Dropdown {
  static initAttrs(attrs: ISplitDropdownAttrs) {
    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Dropdown--split');
    attrs.menuClassName = classList(attrs.menuClassName, 'Dropdown-menu--right');
  }

  getButton(children: Mithril.ChildArray): Mithril.Vnode<any, any> {
    // Make a copy of the attrs of the first child component. We will assign
    // these attrs to a new button, so that it has exactly the same behaviour as
    // the first child.
    const firstChild = this.getFirstChild(children);
    const buttonAttrs = Object.assign({}, firstChild?.attrs);
    buttonAttrs.className = classList(buttonAttrs.className, 'SplitDropdown-button Button', this.attrs.buttonClassName);

    return (
      <>
        <Button {...buttonAttrs}>{firstChild.children}</Button>
        <button
          className={'Dropdown-toggle Button Button--icon ' + this.attrs.buttonClassName}
          aria-haspopup="menu"
          aria-label={this.attrs.accessibleToggleLabel}
          data-toggle="dropdown"
        >
          {this.attrs.icon ? icon(this.attrs.icon, { className: 'Button-icon' }) : null}
          {icon('fas fa-caret-down', { className: 'Button-caret' })}
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
