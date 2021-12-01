import type Mithril from 'mithril';
import type { RouteResolver } from '../Application';
import type { default as Component, ComponentAttrs } from '../Component';

/**
 * Generates a route resolver for a given component.
 *
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver<
  Attrs extends ComponentAttrs,
  Comp extends Component<Attrs & { routeName: string }>,
  RouteArgs extends Record<string, unknown> = {}
> implements RouteResolver<Attrs, Comp, RouteArgs>
{
  component: new () => Comp;
  routeName: string;

  constructor(component: new () => Comp, routeName: string) {
    this.component = component;
    this.routeName = routeName;
  }

  /**
   * When a route change results in a changed key, a full page
   * rerender occurs. This method can be overriden in subclasses
   * to prevent rerenders on some route changes.
   */
  makeKey(): string {
    return this.routeName + JSON.stringify(m.route.param());
  }

  makeAttrs(vnode: Mithril.Vnode<Attrs, Comp>): Attrs & { routeName: string } {
    return {
      ...vnode.attrs,
      routeName: this.routeName,
    };
  }

  onmatch(args: RouteArgs, requestedPath: string, route: string): { new (): Comp } {
    return this.component;
  }

  render(vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children {
    return [{ ...vnode, attrs: this.makeAttrs(vnode), key: this.makeKey() }];
  }
}
