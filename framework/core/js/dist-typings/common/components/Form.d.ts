import type { ComponentAttrs } from '../Component';
import Component from '../Component';
import type Mithril from 'mithril';
export interface IFormAttrs extends ComponentAttrs {
    label?: string;
    description?: string;
}
export default class Form<CustomAttrs extends IFormAttrs = IFormAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
