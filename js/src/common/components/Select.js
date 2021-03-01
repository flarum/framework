import Component from '../Component';
import icon from '../helpers/icon';
import withAttr from '../utils/withAttr';

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following attrs:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 */
export default class Select extends Component {
  view() {
    const { options, onchange, value, disabled } = this.attrs;

    return (
      <span className="Select">
        <select
          className="Select-input FormControl"
          onchange={onchange ? withAttr('value', onchange.bind(this)) : undefined}
          value={value}
          disabled={disabled}
        >
          {Object.keys(options).map((key) => (
            <option value={key}>{options[key]}</option>
          ))}
        </select>
        {icon('fas fa-sort', { className: 'Select-caret' })}
      </span>
    );
  }
}
