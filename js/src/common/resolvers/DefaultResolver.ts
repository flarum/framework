import Mithril from 'mithril';

/**
 * Generates a route resolver for a given component, to which it provides
 * a key as a 'routeName' attr.
 */
export default class DefaultResolver {
  component: Mithril.Component;
  routeName: string;

  constructor(component, routeName) {
    this.component = component;
    this.routeName = routeName;
  }

  makeKey() {
    return this.routeName + JSON.stringify(m.route.param());
  }

  onmatch(args, requestedPath, route) {
    return this.component;
  }

  render(vnode) {
    return { ...vnode, routeName: this.routeName, key: this.makeKey() };
  }
}
