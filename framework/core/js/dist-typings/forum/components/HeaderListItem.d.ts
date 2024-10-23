import type { ComponentAttrs } from '../../common/Component';
import type Mithril from 'mithril';
import Component from '../../common/Component';
export interface IHeaderListItemAttrs extends ComponentAttrs {
    avatar: Mithril.Children;
    icon: string;
    content: string;
    excerpt: string;
    datetime?: Date;
    href: string;
    onclick?: (e: Event) => void;
    actions?: Mithril.Children;
}
export default class HeaderListItem<CustomAttrs extends IHeaderListItemAttrs = IHeaderListItemAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
}
