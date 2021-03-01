import Component from '../Component';
import LoadingIndicator from './LoadingIndicator';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import withAttr from '../utils/withAttr';

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
export default class Checkbox extends Component {
  view(vnode) {
    // Sometimes, false is stored in the DB as '0'. This is a temporary
    // conversion layer until a more robust settings encoding is introduced
    if (this.attrs.state === '0') this.attrs.state = false;

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
        <div className="Checkbox-display">{this.getDisplay()}</div>
        {vnode.children}
      </label>
    );
  }

  /**
   * Get the template for the checkbox's display (tick/cross icon).
   *
   * @return {*}
   * @protected
   */
  getDisplay() {
    return this.attrs.loading ? <LoadingIndicator size="tiny" /> : icon(this.attrs.state ? 'fas fa-check' : 'fas fa-times');
  }

  /**
   * Run a callback when the state of the checkbox is changed.
   *
   * @param {Boolean} checked
   * @protected
   */
  onchange(checked) {
    if (this.attrs.onchange) this.attrs.onchange(checked, this);
  }
}
