import type { FlarumGenericRoute, RouteResolver } from '../Application';
import type Component from '../Component';
import DefaultResolver from '../resolvers/DefaultResolver';

/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril, and wraps them in route resolvers
 * to provide each route with the current route name.
 *
 * @see https://mithril.js.org/route.html#signature
 */
export default function mapRoutes(routes: Record<string, FlarumGenericRoute>, basePath: string = '') {
  const map: Record<
    string,
    RouteResolver<Record<string, unknown>, Component<{ routeName: string; [key: string]: unknown }>, Record<string, unknown>>
  > = {};

  for (const routeName in routes) {
    const route = routes[routeName];

    if ('resolver' in route) {
      map[basePath + route.path] = route.resolver;
    } else if ('component' in route) {
      const resolverClass = 'resolverClass' in route ? route.resolverClass! : DefaultResolver;
      map[basePath + route.path] = new resolverClass(route.component, routeName);
    } else {
      throw new Error(`Either a resolver or a component must be provided for the route [${routeName}]`);
    }
  }

  return map;
}
