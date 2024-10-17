import app from '../app';
import classList from '../utils/classList';
import Dropdown from './Dropdown';
import Mithril from 'mithril';
import Button from './Button';
import Tooltip from './Tooltip';
import Select, { ISelectAttrs, Option } from './Select';

export interface IMultiSelectAttrs extends ISelectAttrs {}

export default class MultiSelect<CustomAttrs extends IMultiSelectAttrs = IMultiSelectAttrs> extends Select<CustomAttrs> {
  protected selected: string[] = [];

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.selected = this.attrs.value || [];
  }

  input(): JSX.Element {
    const {
      options,
      onchange,
      disabled,
      className,
      class: _class,

      ...domAttrs
    } = this.attrs;

    return (
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
              className={classList('Dropdown-item', `Dropdown-item--${key}`, { disabled })}
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
