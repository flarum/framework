import Component, { ComponentAttrs } from '../Component';
import LoadingIndicator from './LoadingIndicator';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import withAttr from '../utils/withAttr';
import type Mithril from 'mithril';

export interface ICheckboxAttrs extends ComponentAttrs {
  state?: boolean;
  loading?: boolean;
  disabled?: boolean;
  onchange: (checked: boolean, component: Checkbox<this>) => void;
}

/**
 * The `Checkbox` component defines a checkbox input.
 *
 * ### Attrs
 *
 * - `state` Whether or not the checkbox is checked.
 * - `className` The class name for the root element.
 * - `disabled` Whether or not the checkbox is disabled.
 * - `loading` Whether or not the checkbox is loading.
 * - `onchange` A callback to run when the checkbox is checked/unchecked.
 * - `children` A text label to display next to the checkbox.
 */
export default class Checkbox<CustomAttrs extends ICheckboxAttrs = ICheckboxAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const className = classList([
      'Checkbox',
      this.attrs.state ? 'on' : 'off',
      this.attrs.className,
      this.attrs.loading && 'loading',
      this.attrs.disabled && 'disabled',
    ]);

    return (
      <label className={className}>
        <input type="checkbox" checked={this.attrs.state} disabled={this.attrs.disabled} onchange={withAttr('checked', this.onchange.bind(this))} />
        <div className="Checkbox-display" aria-hidden="true">
          {this.getDisplay()}
        </div>
        {vnode.children}
      </label>
    );
  }

  /**
   * Get the template for the checkbox's display (tick/cross icon).
   */
  protected getDisplay(): Mithril.Children {
    return this.attrs.loading ? <LoadingIndicator display="unset" size="small" /> : icon(this.attrs.state ? 'fas fa-check' : 'fas fa-times');
  }

  /**
   * Run a callback when the state of the checkbox is changed.
   */
  protected onchange(checked: boolean): void {
    if (this.attrs.onchange) this.attrs.onchange(checked, this);
  }
}
