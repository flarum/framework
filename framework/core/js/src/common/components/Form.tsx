import type { ComponentAttrs } from '../Component';
import Component from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';

export interface IFormAttrs extends ComponentAttrs {
  label?: string;
  description?: string;
}

export default class Form<CustomAttrs extends IFormAttrs = IFormAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { label, description, className, ...attrs } = vnode.attrs;

    return (
      <div className={classList('Form', className)} {...attrs}>
        {(label || description) && (
          <div className="Form-header">
            {label && <label>{label}</label>}
            {description && <p className="helpText">{description}</p>}
          </div>
        )}
        <div className="Form-body">{vnode.children}</div>
      </div>
    );
  }
}
