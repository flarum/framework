import Dropdown from 'flarum/components/Dropdown';
import icon from 'flarum/helpers/icon';

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 */
export default class SelectDropdown extends Dropdown {
  static initProps(props) {
    super.initProps(props);

    props.className += ' select-dropdown';
  }

  getButtonContent() {
    const activeChild = this.props.children.filter(child => child.props.active)[0];
    const label = activeChild && activeChild.props.label;

    return [
      <span className="label">{label}</span>,
      icon('sort', {className: 'caret'})
    ];
  }
}
