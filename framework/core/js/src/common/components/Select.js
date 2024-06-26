import Component from '../Component';
import withAttr from '../utils/withAttr';
import classList from '../utils/classList';
import Icon from './Icon';

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
          {Object.keys(options).map((key) => {
            const option = options[key];

            let label;
            let disabled = false;

            if (typeof option === 'object' && option.label) {
              label = option.label;
              disabled = option.disabled ?? false;
            } else {
              label = option;
            }

            return (
              <option value={key} disabled={disabled}>
                {label}
              </option>
            );
          })}
        </select>
        <Icon name="fas fa-sort" className="Select-caret" />
      </span>
    );
  }
}
