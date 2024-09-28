import app from '../../common/app';
import Component from '../Component';
import Icon from './Icon';
import LoadingIndicator from './LoadingIndicator';
import classList from '../utils/classList';
import Button from './Button';
import Stream from '../utils/Stream';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';

export interface IInputAttrs extends ComponentAttrs {
  className?: string;
  prefixIcon?: string;
  clearable?: boolean;
  clearLabel?: string;
  loading?: boolean;
  inputClassName?: string;
  onchange?: (value: string) => void;
  value?: string;
  stream?: Stream<string>;
  type?: string;
  ariaLabel?: string;
  placeholder?: string;
  readonly?: boolean;
  disabled?: boolean;
  renderInput?: (attrs: any) => Mithril.Children;
  inputAttrs?: {
    className?: string;
    [key: string]: any;
  };
}

export default class Input<CustomAttrs extends IInputAttrs = IInputAttrs> extends Component<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { className: inputClassName, ...inputAttrs } = this.attrs.inputAttrs || {};

    const value = this.attrs.value || this.attrs.stream?.() || '';

    return (
      <div
        className={classList('Input', this.attrs.className, {
          'Input--withPrefix': this.attrs.prefixIcon,
          'Input--withClear': this.attrs.clearable,
        })}
      >
        {this.attrs.prefixIcon && <Icon name={classList(this.attrs.prefixIcon, 'Input-prefix-icon')} />}
        {this.input({ inputClassName, value, inputAttrs })}
        {this.attrs.loading && <LoadingIndicator size="small" display="inline" containerClassName="Button Button--icon Button--link" />}
        {this.attrs.clearable && value && !this.attrs.loading && (
          <Button
            className="Input-clear Button Button--icon Button--link"
            onclick={this.clear.bind(this)}
            aria-label={this.attrs.clearLabel || app.translator.trans('core.lib.input.clear_button')}
            type="button"
            icon="fas fa-times-circle"
          />
        )}
      </div>
    );
  }

  input({ inputClassName, value, inputAttrs }: any) {
    const attrs = {
      className: classList('FormControl', inputClassName),
      type: this.attrs.type || 'text',
      value: value,
      oninput: (e: InputEvent) => this.onchange?.((e.target as HTMLInputElement).value),
      'aria-label': this.attrs.ariaLabel,
      placeholder: this.attrs.placeholder,
      readonly: this.attrs.readonly || undefined,
      disabled: this.attrs.disabled || undefined,
      ...inputAttrs,
    };

    if (this.attrs.renderInput) {
      return this.attrs.renderInput(attrs);
    }

    return <input {...attrs} />;
  }

  onchange(value: string) {
    if (this.attrs.stream) {
      this.attrs.stream(value);
    } else {
      this.attrs.onchange?.(value);
    }
  }

  clear() {
    this.onchange('');
  }
}
