import Component from '../../common/Component';
import type { ComponentAttrs } from '../../common/Component';
import Mithril from 'mithril';
export interface IFormSectionGroupAttrs extends ComponentAttrs {
}
export default class FormSectionGroup<CustomAttrs extends IFormSectionGroupAttrs = IFormSectionGroupAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
export interface IFormSectionAttrs extends ComponentAttrs {
    label: any;
}
export declare class FormSection<CustomAttrs extends IFormSectionAttrs = IFormSectionAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
