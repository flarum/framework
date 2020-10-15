import Mithril from 'mithril';

/**
 * Generates a route resolver for a given component.
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver {
  component: Mithril.Component;
  routeName: string;

  constructor(component, routeName) {
    this.component = component;
    this.routeName = routeName;
  }

  /**
   * When a route change results in a changed key, a full page
   * rerender occurs. This method can be overriden in subclasses
   * to prevent rerenders on some route changes.
   */
  makeKey() {
    return this.routeName + JSON.stringify(m.route.param());
  }

  onmatch(args, requestedPath, route) {
    return this.component;
  }

  render(vnode) {
    return [{ ...vnode, routeName: this.routeName, key: this.makeKey() }];
  }
}
