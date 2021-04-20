import Component from '../Component';

/**
 * Generates a route resolver for a given component.
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver {
  component: Component | Function;
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

  makeAttrs(vnode) {
    return {
      ...vnode.attrs,
      routeName: this.routeName,
    };
  }

  async onmatch(args, requestedPath, route) {
    if (typeof this.component.component === 'function') {
      return this.component;
    } else {
      const component = await this.component();
      return component.default;
    }
  }

  render(vnode) {
    return [{ ...vnode, attrs: this.makeAttrs(vnode), key: this.makeKey() }];
  }
}
