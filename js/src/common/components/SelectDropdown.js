import Dropdown from './Dropdown';
import icon from '../helpers/icon';

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 *
 * ### Attrs
 *
 * - `caretIcon`
 * - `defaultLabel`
 */
export default class SelectDropdown extends Dropdown {
  initAttrs(attrs) {
    attrs.caretIcon = typeof attrs.caretIcon !== 'undefined' ? attrs.caretIcon : 'fas fa-sort';

    super.initAttrs(attrs);

    attrs.className += ' Dropdown--select';
  }

  getButtonContent(children) {
    const activeChild = children.filter((child) => child.attrs.active)[0];
    let label = (activeChild && activeChild.children) || this.attrs.defaultLabel;

    if (label instanceof Array) label = label[0];

    return [<span className="Button-label">{label}</span>, icon(this.attrs.caretIcon, { className: 'Button-caret' })];
  }
}
