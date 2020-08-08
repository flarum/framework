import Button from './Button';

/**
 * The `LinkButton` component defines a `Button` which links to a route.
 *
 * ### Props
 *
 * All of the props accepted by `Button`, plus:
 *
 * - `active` Whether or not the page that this button links to is currently
 *   active.
 * - `href` The URL to link to. If the current URL `m.route()` matches this,
 *   the `active` prop will automatically be set to true.
 */
export default class LinkButton extends Button {
  initAttrs(attrs) {
    super.initAttrs(attrs);

    attrs.active = this.constructor.isActive(attrs);
  }

  view(vnode) {
    const vdom = super.view(vnode);

    vdom.tag = m.route.Link;
    vdom.attrs.active = String(vdom.attrs.active);

    return vdom;
  }

  /**
   * Determine whether a component with the given props is 'active'.
   *
   * @param {Object} props
   * @return {Boolean}
   */
  static isActive(attrs) {
    return typeof attrs.active !== 'undefined' ? attrs.active : m.route.get() === attrs.href;
  }
}
