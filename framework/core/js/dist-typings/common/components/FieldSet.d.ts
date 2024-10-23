import Component, { ComponentAttrs } from '../Component';
import Mithril from 'mithril';
export interface IFieldSetAttrs extends ComponentAttrs {
    label: string;
    description?: string;
}
/**
 * The `FieldSet` component defines a collection of fields, displayed in a list
 * underneath a title.
 *
 * The children should be an array of items to show in the fieldset.
 */
export default class FieldSet<CustomAttrs extends IFieldSetAttrs = IFieldSetAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
