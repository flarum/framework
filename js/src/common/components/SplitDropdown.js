import Dropdown from './Dropdown';
import Button from './Button';
import icon from '../helpers/icon';

/**
 * The `SplitDropdown` component is similar to `Dropdown`, but the first child
 * is displayed as its own button prior to the toggle button.
 */
export default class SplitDropdown extends Dropdown {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.className += ' Dropdown--split';
    attrs.menuClassName += ' Dropdown-menu--right';
  }

  getButton(children) {
    // Make a copy of the attrs of the first child component. We will assign
    // these attrs to a new button, so that it has exactly the same behaviour as
    // the first child.
    const firstChild = this.getFirstChild(children);
    const buttonAttrs = Object.assign({}, firstChild.attrs);
    buttonAttrs.className = (buttonAttrs.className || '') + ' SplitDropdown-button Button ' + this.attrs.buttonClassName;

    return [
      Button.component(buttonAttrs, firstChild.children),
      <button className={'Dropdown-toggle Button Button--icon ' + this.attrs.buttonClassName} data-toggle="dropdown">
        {icon(this.attrs.icon, { className: 'Button-icon' })}
        {icon('fas fa-caret-down', { className: 'Button-caret' })}
      </button>,
    ];
  }

  /**
   * Get the first child. If the first child is an array, the first item in that
   * array will be returned.
   *
   * @return {*}
   * @protected
   */
  getFirstChild(children) {
    let firstChild = children;

    while (firstChild instanceof Array) firstChild = firstChild[0];

    return firstChild;
  }
}
