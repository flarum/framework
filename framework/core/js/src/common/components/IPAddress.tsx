import Component, { ComponentAttrs } from '../Component';
import ItemList from '../utils/ItemList';
import type Mithril from 'mithril';

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
  ip!: string;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.ip = this.attrs.ip || '';
  }

  view() {
    return <span className="IPAddress">{this.viewItems().toArray()}</span>;
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('ip', <>{this.ip}</>, 100);

    return items;
  }
}
