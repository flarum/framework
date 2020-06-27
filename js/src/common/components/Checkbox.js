import Component from '../Component';
import LoadingIndicator from './LoadingIndicator';
import icon from '../helpers/icon';

/**
 * The `Checkbox` component defines a checkbox input.
 *
 * ### Props
 *
 * - `state` Whether or not the checkbox is checked.
 * - `className` The class name for the root element.
 * - `disabled` Whether or not the checkbox is disabled.
 * - `loading` Whether or not the checkbox is loading.
 * - `onchange` A callback to run when the checkbox is checked/unchecked.
 * - `children` A text label to display next to the checkbox.
 */
export default class Checkbox extends Component {
  view() {
    // Sometimes, false is stored in the DB as '0'. This is a temporary
    // conversion layer until a more robust settings encoding is introduced
    if (this.props.state === '0') this.props.state = false;
    let className = 'Checkbox ' + (this.props.state ? 'on' : 'off') + ' ' + (this.props.className || '');
    if (this.props.loading) className += ' loading';
    if (this.props.disabled) className += ' disabled';

    return (
      <label className={className}>
        <input type="checkbox" checked={this.props.state} disabled={this.props.disabled} onchange={m.withAttr('checked', this.onchange.bind(this))} />
        <div className="Checkbox-display">{this.getDisplay()}</div>
        {this.props.children}
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
    return this.props.loading ? LoadingIndicator.component({ size: 'tiny' }) : icon(this.props.state ? 'fas fa-check' : 'fas fa-times');
  }

  /**
   * Run a callback when the state of the checkbox is changed.
   *
   * @param {Boolean} checked
   * @protected
   */
  onchange(checked) {
    if (this.props.onchange) this.props.onchange(checked, this);
  }
}
