import Mithril from 'mithril';
import app from '../app';
import classList from '../utils/classList';
import applyIconStyle from '../utils/applyIconStyle';
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
  view(vnode: Mithril.Vnode<CustomAttrs, this>): Mithril.Children {
    const { name, noStyleOverride, ...attrs } = vnode.attrs;

    // Admins can force every icon to a particular FontAwesome style
    // (e.g. `fa-duotone fa-light`); empty means icons render as declared.
    const forcedStyle = noStyleOverride ? null : app?.forum?.attribute<string | null>('fontAwesomeForcedStyle');

    // @ts-ignore
    attrs.className = classList('icon', forcedStyle ? applyIconStyle(name, forcedStyle) : name, attrs.className);

    return <i aria-hidden="true" {...attrs} />;
  }
}
