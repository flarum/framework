import Component, { ComponentProps } from "../Component";
import icon from "../helpers/icon";

export interface SelectProps extends ComponentProps {
    /**u
     * Whether or not the select is disabled.
     */
    disabled: boolean;

    /**
     * A callback to run when the selected value is changed.
     */
    onchange?: Function;

    /**
     * An object of options
     */
    options: {
        [index: string]: string;
    };

    /**
     * The current value of the select.
     */
    value: boolean;
}

/**
 * The `Select` component displays a <select> input, surrounded with some extra
 * elements for styling. It accepts the following props:
 *
 * - `options` A map of option values to labels.
 * - `onchange` A callback to run when the selected value is changed.
 * - `value` The value of the selected option.
 * - `disabled` Disabled state for the input.
 */
export default class Select<
    T extends SelectProps = SelectProps
> extends Component<SelectProps> {
    view() {
        return (
            <span className="Select">
                <select
                    className="Select-input FormControl"
                    disabled={this.props.disabled}
                    onchange={m.withAttr("value", this.onchange.bind(this))}
                    value={this.props.value}
                >
                    {Object.keys(this.props.options).map((key: string) => (
                        <option value={key}>{this.props.options[key]}</option>
                    ))}
                </select>
                {icon("fas fa-sort", { className: "Select-caret" })}
            </span>
        );
    }

    /**
     * Run a callback when the state of the checkbox is changed.
     */
    protected onchange() {
        if (this.props.onchange) this.props.onchange(this);
    }
}
