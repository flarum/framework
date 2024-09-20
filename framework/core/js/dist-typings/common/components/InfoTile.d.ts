import Component from '../Component';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
export interface IInfoTileAttrs extends ComponentAttrs {
    icon?: string;
    iconElement?: Mithril.Children;
}
export default class InfoTile<CustomAttrs extends IInfoTileAttrs = IInfoTileAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
    icon(): Mithril.Children;
}
