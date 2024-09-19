import app from '../app';
import Component, { type ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import Dropdown from './Dropdown';
import Mithril from 'mithril';
import Button from './Button';
import Tooltip from './Tooltip';

export type Option = {
  label: string;
  disabled?: boolean | ((value: string[]) => boolean);
  tooltip?: string;
};

export interface IMultiSelectAttrs extends ComponentAttrs {
  options: Record<string, string | Option>;
  onchange?: (value: string[]) => void;
  value?: string[];
  disabled?: boolean;
  wrapperAttrs?: Record<string, string>;
}

/**
 * The `MultiSelect` component displays an input with selected elements.
 * With a dropdown to select multiple options.
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 * - `wrapperAttrs` A map of attrs to be passed to the DOM element wrapping the input.
 *
 * Other attributes are passed directly to the input element rendered to the DOM.
 */
export default class MultiSelect<CustomAttrs extends IMultiSelectAttrs = IMultiSelectAttrs> extends Component<CustomAttrs> {
  protected selected: string[] = [];

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.selected = this.attrs.value || [];
  }

  view() {
    const {
      options,
      onchange,
      disabled,
      className,
      class: _class,

      // Destructure the `wrapperAttrs` object to extract the `className` for passing to `classList()`
      // `= {}` prevents errors when `wrapperAttrs` is undefined
      wrapperAttrs: { className: wrapperClassName, class: wrapperClass, ...wrapperAttrs } = {},

      ...domAttrs
    } = this.attrs;

    return (
      <span className={classList('Select MultiSelect', wrapperClassName, wrapperClass)} {...wrapperAttrs}>
        <Dropdown
          disabled={disabled}
          buttonClassName="Button"
          buttonAttrs={{ disabled }}
          label={
            Object.keys(options)
              .filter((key) => this.selected.includes(key))
              .map((key) => (typeof options[key] === 'string' ? options[key] : (options[key] as Option).label))
              .join(', ') || app.translator.trans('core.lib.multi_select.placeholder')
          }
        >
          {Object.keys(options).map((key) => {
            const option = options[key];
            const label = typeof option === 'string' ? option : option.label;
            const tooltip = typeof option !== 'string' && option.tooltip;
            let disabled = typeof option !== 'string' && option.disabled;

            if (typeof disabled === 'function') {
              disabled = disabled(this.selected);
            }

            const button = (
              <Button
                type="button"
                className={classList('Dropdown-item', { disabled })}
                onclick={this.toggle.bind(this, key)}
                disabled={disabled}
                icon={this.selected.includes(key) ? 'fas fa-check' : 'fas fa-empty'}
              >
                {label}
              </Button>
            );

            if (tooltip) {
              return <Tooltip text={tooltip}>{button}</Tooltip>;
            }

            return button;
          })}
        </Dropdown>
      </span>
    );
  }

  select(value: string) {
    this.selected.push(value);
  }

  unselect(value: string) {
    this.selected = this.selected.filter((v) => v !== value);
  }

  toggle(value: string, e: MouseEvent) {
    e.stopPropagation();

    if (this.selected.includes(value)) {
      this.unselect(value);
    } else {
      this.select(value);
    }

    if (this.attrs.onchange) {
      this.attrs.onchange(this.selected);
    }
  }
}
