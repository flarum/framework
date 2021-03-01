import Component from '../Component';
import listItems from '../helpers/listItems';

/**
 * The `FieldSet` component defines a collection of fields, displayed in a list
 * underneath a title. Accepted properties are:
 *
 * - `className` The class name for the fieldset.
 * - `label` The title of this group of fields.
 *
 * The children should be an array of items to show in the fieldset.
 */
export default class FieldSet extends Component {
  view(vnode) {
    return (
      <fieldset className={this.attrs.className}>
        <legend>{this.attrs.label}</legend>
        <ul>{listItems(vnode.children)}</ul>
      </fieldset>
    );
  }
}
