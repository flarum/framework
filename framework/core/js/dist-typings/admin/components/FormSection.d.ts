import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import Mithril from 'mithril';
export interface IFormSectionAttrs extends ComponentAttrs {
    label: any;
}
export default class FormSection<CustomAttrs extends IFormSectionAttrs = IFormSectionAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
