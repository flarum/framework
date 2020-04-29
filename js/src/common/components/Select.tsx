import Component, { ComponentProps } from '../Component';
import icon from '../helpers/icon';

export interface SelectProps extends ComponentProps {
    /**
     * Disabled state for the input.
     */
    disabled?: boolean;

    /**
     * A callback to run when the selected value is changed.
     */
    onchange?: Function;

    /**
     * A map of option values to labels.
     */
    options: {
        [key: string]: string;
    };

    /**
     * The value of the selected option.
     */
    value: boolean;
}

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling.
 */
export default class Select<T extends SelectProps = SelectProps> extends Component<T> {
    view() {
        return (
            <span className="Select">
                <select
                    className="Select-input FormControl"
                    disabled={this.props.disabled}
                    onchange={m.withAttr('value', this.onchange.bind(this))}
                    value={this.props.value}
                >
                    {Object.keys(this.props.options).map((key: string) => (
                        <option value={key}>{this.props.options[key]}</option>
                    ))}
                </select>
                {icon('fas fa-sort', { className: 'Select-caret' })}
            </span>
        );
    }

    /**
     * Run a callback when the state of the checkbox is changed.
     */
    protected onchange(value) {
        if (this.props.onchange) this.props.onchange(value);
    }
}
