import type { ComponentAttrs } from '../../common/Component';
import Component from '../../common/Component';
import type ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
export interface IHeaderListAttrs extends ComponentAttrs {
    title: string;
    controls?: ItemList<Mithril.Children>;
    hasItems: boolean;
    loading?: boolean;
    emptyText: string;
    loadMore?: () => void;
    footer?: () => Mithril.Children;
}
export default class HeaderList<CustomAttrs extends IHeaderListAttrs = IHeaderListAttrs> extends Component<CustomAttrs> {
    $content: JQuery<any> | null;
    $scrollParent: JQuery<any> | null;
    boundScrollHandler: (() => void) | null;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    scrollHandler(): void;
    /**
     * If the NotificationList component isn't in a panel (e.g. on NotificationPage when mobile),
     * we need to listen to scroll events on the window, and get scroll state from the body.
     */
    inPanel(): boolean;
}
