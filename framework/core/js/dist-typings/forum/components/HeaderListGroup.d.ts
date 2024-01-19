import type { ComponentAttrs } from '../../common/Component';
import Component from '../../common/Component';
import type Mithril from 'mithril';
export interface IHeaderListGroupAttrs extends ComponentAttrs {
    label: Mithril.Children;
}
export default class HeaderListGroup<CustomAttrs extends IHeaderListGroupAttrs = IHeaderListGroupAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
