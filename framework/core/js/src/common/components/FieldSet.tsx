import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
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
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    return (
      <div className={classList('FieldSet', this.attrs.className)} role="group" aria-label={this.attrs.label} aria-disabled={false}>
        <label className="FieldSet-label" aria-hidden="true">
          {this.attrs.label}
        </label>
        {this.attrs.description ? <div className="FieldSet-description helpText">{this.attrs.description}</div> : null}
        <div className="FieldSet-items">{vnode.children}</div>
      </div>
    );
  }
}
