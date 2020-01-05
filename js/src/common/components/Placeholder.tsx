import Component, {ComponentProps} from '../Component';

export interface PlaceholderProps extends ComponentProps {
    text: string
}

/**
 * The `Placeholder` component displays a muted text with some call to action,
 * usually used as an empty state.
 */
export default class Placeholder extends Component<PlaceholderProps> {
    view() {
        return (
            <div className="Placeholder">
                <p>{this.props.text}</p>
            </div>
        );
    }
}
