import Mithril from 'mithril';
import type { ComponentAttrs } from '../Component';
import Component from '../Component';
export interface IIconAttrs extends ComponentAttrs {
    /** The full icon class, prefix and the icon’s name. */
    name: string;
    /**
     * Escape hatch: keep the declared style even when the forum forces one.
     * For icons whose meaning depends on their style — e.g. a solid vs regular
     * star conveying "starred" state.
     */
    noStyleOverride?: boolean;
}
export default class Icon<CustomAttrs extends IIconAttrs = IIconAttrs> extends Component<CustomAttrs> {
    view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children;
}
