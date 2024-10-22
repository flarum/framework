import Component, { type ComponentAttrs } from '../Component';
import withAttr from '../utils/withAttr';
import classList from '../utils/classList';
import Icon from './Icon';

export type Option = {
  label: string;
  disabled?: boolean | ((value: any) => boolean);
  tooltip?: string;
};

export interface ISelectAttrs extends ComponentAttrs {
  options: Record<string, string | Option>;
  onchange?: (value: any) => void;
  value?: any;
  disabled?: boolean;
  wrapperAttrs?: Record<string, string>;
}

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
export default class Select<CustomAttrs extends ISelectAttrs = ISelectAttrs> extends Component<CustomAttrs> {
  view() {
    const {
      // Destructure the `wrapperAttrs` object to extract the `className` for passing to `classList()`
      // `= {}` prevents errors when `wrapperAttrs` is undefined
      wrapperAttrs: { className: wrapperClassName, class: wrapperClass, ...wrapperAttrs } = {},
    } = this.attrs;

    return (
      <span className={classList('Select', wrapperClassName, wrapperClass)} {...wrapperAttrs}>
        {this.input()}
      </span>
    );
  }

  input() {
    const {
      options,
      onchange,
      value,
      disabled,
      className,
      class: _class,

      ...domAttrs
    } = this.attrs;

    return (
      <>
        <select
          className={classList('Select-input FormControl', className, _class)}
          onchange={onchange ? withAttr('value', onchange.bind(this)) : undefined}
          value={value}
          disabled={disabled}
          {...domAttrs}
        >
          {Object.keys(options).map((key) => {
            const option = options[key];
            const label = typeof option === 'object' && 'label' in option ? option.label : option;
            let disabled = typeof option === 'object' && 'disabled' in option ? option.disabled : false;

            if (typeof disabled === 'function') {
              disabled = disabled(value ?? null);
            }

            return (
              <option value={key} disabled={disabled}>
                {label}
              </option>
            );
          })}
        </select>
        <Icon name="fas fa-sort" className="Select-caret" />
      </>
    );
  }
}
