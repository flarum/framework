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

  onmatch(args, requestedPath, route) {
    return this.component;
  }

  render(vnode) {
    vnode.attrs.routeName = this.routeName;

    return vnode;
  }
}
