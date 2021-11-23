import type Mithril from 'mithril';
import Component, { ComponentAttrs } from '../Component';
export default class ColorPreviewInput extends Component {
    value?: string;
    view(vnode: Mithril.Vnode<ComponentAttrs, this>): JSX.Element;
}
