import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import Mithril from 'mithril';
import classList from '../../common/utils/classList';

export interface IFormSectionAttrs extends ComponentAttrs {
  label: any;
}

export default class FormSection<CustomAttrs extends IFormSectionAttrs = IFormSectionAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { className, ...attrs } = this.attrs;

    return (
      <div className={classList('FormSection', className)} {...attrs}>
        <label>{this.attrs.label}</label>
        <div className="FormSection-body">{vnode.children}</div>
      </div>
    );
  }
}
