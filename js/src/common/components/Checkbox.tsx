import Component, { ComponentProps } from '../Component';
import LoadingIndicator from './LoadingIndicator';
import icon from '../helpers/icon';

export interface CheckboxProps extends ComponentProps {
    /**
     * Whether or not the checkbox is checked
     */
    state: boolean;

    /**
     * Whether or not the checkbox is disabled.
     */
    disabled: boolean;

    /**
     * A callback to run when the checkbox is checked/unchecked.
     */
    onchange?: Function;
}

/**
 * The `Checkbox` component defines a checkbox input.
 */
export default class Checkbox<T extends CheckboxProps = CheckboxProps> extends Component<CheckboxProps> {
    /**
     * Whether or not the checkbox's value is in the process of being saved.
     */
    loading = false;

    view() {
        const className = classNames(
            'Checkbox',
            this.props.className,
            this.props.state ? 'on' : 'off',
            this.loading && 'loading',
            this.props.disabled && 'disabled'
        );

        return (
            <label className={className}>
                <input
                    type="checkbox"
                    checked={this.props.state}
                    disabled={this.props.disabled}
                    onchange={m.withAttr('checked', this.onchange.bind(this))}
                />
                <div className="Checkbox-display">{this.getDisplay()}</div>
                {this.props.children}
            </label>
        );
    }

    /**
     * Get the template for the checkbox's display (tick/cross icon).
     */
    protected getDisplay() {
        return this.loading ? LoadingIndicator.component({ size: 'tiny' }) : icon(this.props.state ? 'fas fa-check' : 'fas fa-times');
    }

    /**
     * Run a callback when the state of the checkbox is changed.
     */
    protected onchange(checked: boolean) {
        if (this.props.onchange) this.props.onchange(checked, this);
    }
}
