import type User from '../models/User';
import type { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import Component from '../Component';
export interface IAvatarAttrs extends ComponentAttrs {
    user: User | null;
}
export default class Avatar<CustomAttrs extends IAvatarAttrs = IAvatarAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
}
