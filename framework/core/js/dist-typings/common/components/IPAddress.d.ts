import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import ItemList from '../utils/ItemList';
export interface IIPAddressAttrs extends ComponentAttrs {
    ip: string | undefined | null;
}
/**
 * A component to wrap an IP address for display.
 * Designed to be customizable for different use cases.
 *
 * @example
 * <IPAddress ip="127.0.0.1" />
 * @example
 * <IPAddress ip={post.data.attributes.ipAddress} />
 */
export default class IPAddress<CustomAttrs extends IIPAddressAttrs = IIPAddressAttrs> extends Component<CustomAttrs> {
    ip: string;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    viewItems(): ItemList<Mithril.Children>;
}
