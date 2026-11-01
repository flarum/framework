import Component, { ComponentAttrs } from '../Component';
import classList from '../utils/classList';
import type Mithril from 'mithril';

export interface IIconImageAttrs extends ComponentAttrs {
  /** The 1x image. */
  src: string;
  /** The HiDPI variants, if any. */
  srcset?: string | null;
}

/**
 * An uploaded image shown in place of a Font Awesome icon, such as a group's
 * or a tag's. It sits beside a label that names it, so it has empty alt text.
 */
export default class IconImage<CustomAttrs extends IIconImageAttrs = IIconImageAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const { src, srcset, className, ...attrs } = vnode.attrs;

    return <img {...attrs} className={classList('IconImage', className)} src={src} srcset={srcset || undefined} alt="" decoding="async" />;
  }
}
