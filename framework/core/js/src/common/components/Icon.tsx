import Mithril from 'mithril';
import classList from '../utils/classList';
import type { ComponentAttrs } from '../Component';
import Component from '../Component';

export interface IIconAttrs extends ComponentAttrs {
  /** The full icon class, prefix and the icon’s name. */
  name: string;
}

export default class Icon<CustomAttrs extends IIconAttrs = IIconAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { name, ...attrs } = vnode.attrs;

    // @ts-ignore
    attrs.className = classList('icon', name, attrs.className);

    return <i aria-hidden="true" {...attrs} />;
  }
}
