import Component, { type ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface IPillAttrs extends ComponentAttrs {
    deletable?: boolean;
    ondelete?: () => void;
}
export default class Pill<CustomAttrs extends IPillAttrs = IPillAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
