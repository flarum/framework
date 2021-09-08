import Component from '../Component';
import icon from '../helpers/icon';
import withAttr from '../utils/withAttr';
import classList from '../utils/classList';

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following attrs:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 * - `wrapperAttrs` A map of attrs to be passed to the DOM element wrapping the `<select>`
 *
 * Other attributes are passed directly to the `<select>` element rendered to the DOM.
 */
export default class Select extends Component {
  view() {
    const {
      options,
      onchange,
      value,
      disabled,
      className,
      class: _class,

      // Destructure the `wrapperAttrs` object to extract the `className` for passing to `classList()`
      // `= {}` prevents errors when `wrapperAttrs` is undefined
      wrapperAttrs: { className: wrapperClassName, class: wrapperClass, ...wrapperAttrs } = {},

      ...domAttrs
    } = this.attrs;

    return (
      <span className={classList('Select', wrapperClassName, wrapperClass)} {...wrapperAttrs}>
        <select
          className={classList('Select-input FormControl', className, _class)}
          onchange={onchange ? withAttr('value', onchange.bind(this)) : undefined}
          value={value}
          disabled={disabled}
          {...domAttrs}
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
