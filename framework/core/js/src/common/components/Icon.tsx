import Mithril from 'mithril';
import classList from '../utils/classList';
import type { ComponentAttrs } from '../Component';
import Component from '../Component';

export interface IIconAttrs extends ComponentAttrs {
  name: string;
}

/**
 * The `icon` helper displays an icon.
 *
 * @param fontClass The full icon class, prefix and the icon’s name.
 * @param attrs Any other attributes to apply.
 */
export default class Icon<CustomAttrs extends IIconAttrs = IIconAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { name, ...attrs } = vnode.attrs;

    // @ts-ignore
    attrs.className = classList('icon', name, attrs.className);

    return <i aria-hidden="true" {...attrs} />;
  }
}
