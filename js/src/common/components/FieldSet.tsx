import Component, { ComponentProps } from '../Component';
import listItems from '../helpers/listItems';

export interface FieldSetProps extends ComponentProps {
    /**
     * The title of this group of fields
     */
    label: string;
}

/**
 * The `FieldSet` component defines a collection of fields, displayed in a list
 * underneath a title.
 *
 * The children should be an array of items to show in the fieldset.
 */
export default class FieldSet extends Component<FieldSetProps> {
    view() {
        return (
            <fieldset className={this.props.className}>
                <legend>{this.props.label}</legend>
                <ul>{listItems(this.props.children)}</ul>
            </fieldset>
        );
    }
}
