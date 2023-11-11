import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import Mithril from 'mithril';
import classList from '../../common/utils/classList';

export interface IFormSectionGroupAttrs extends ComponentAttrs {}

export default class FormSectionGroup<CustomAttrs extends IFormSectionGroupAttrs = IFormSectionGroupAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { className, ...attrs } = this.attrs;

    return (
      <div className={classList('FormSectionGroup', className)} {...attrs}>
        {vnode.children}
      </div>
    );
  }
}

export interface IFormSectionAttrs extends ComponentAttrs {
  label: any;
}

export class FormSection<CustomAttrs extends IFormSectionAttrs = IFormSectionAttrs> extends Component<CustomAttrs> {
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
