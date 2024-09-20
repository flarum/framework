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
