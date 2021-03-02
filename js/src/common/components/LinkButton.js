import Button from './Button';
import Link from './Link';

/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Attrs
 *
 * All of the attrs accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 * - `force` Whether the page should be fully rerendered. Defaults to `true`.
 */
export default class LinkButton extends Button {
  static initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.active = this.isActive(attrs);
    if (attrs.force === undefined) attrs.force = true;
  }

  view(vnode) {
    const vdom = super.view(vnode);

    vdom.tag = Link;
    vdom.attrs.active = String(vdom.attrs.active);

    return vdom;
  }

  /**
   * Determine whether a component with the given attrs is 'active'.
   *
   * @param {Object} attrs
   * @return {Boolean}
   */
  static isActive(attrs) {
    return typeof attrs.active !== 'undefined' ? attrs.active : m.route.get() === attrs.href;
  }
}
