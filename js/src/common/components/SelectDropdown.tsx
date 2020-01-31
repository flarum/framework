import Dropdown, { DropdownProps } from './Dropdown';
import icon from '../helpers/icon';

export interface SelectDropdownProps extends DropdownProps {
    defaultLabel?: string;
}

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 *
 * ### Props
 *
 * - `caretIcon`
 * - `defaultLabel`
 */
export default class SelectDropdown extends Dropdown<SelectDropdownProps> {
    static initProps(props: SelectDropdownProps) {
        props.caretIcon = typeof props.caretIcon !== 'undefined' ? props.caretIcon : 'fas fa-sort';

        super.initProps(props);

        props.className += ' Dropdown--select';
    }

    getButtonContent() {
        const activeChild = this.props.children.filter(child => child.attrs.active)[0];
        let label = (activeChild && activeChild.attrs.children) || this.props.defaultLabel;

        if (label instanceof Array) label = label[0];

        return [<span className="Button-label">{label}</span>, icon(this.props.caretIcon, { className: 'Button-caret' })];
    }
}
