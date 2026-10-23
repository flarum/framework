import type Mithril from 'mithril';
import Component, { ComponentAttrs } from 'flarum/common/Component';
interface LabelAttrs extends ComponentAttrs {
    type: 'success' | 'error' | 'neutral' | 'warning';
}
export default class Label extends Component<LabelAttrs> {
    view(vnode: Mithril.Vnode<LabelAttrs, this>): JSX.Element;
}
export {};
