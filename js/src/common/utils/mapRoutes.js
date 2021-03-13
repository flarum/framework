import DefaultResolver from '../resolvers/DefaultResolver';

/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril, and wraps them in route resolvers
 * to provide each route with the current route name.
 *
 * @see https://mithril.js.org/route.html#signature
 * @param {Object} routes
 * @param {String} [basePath]
 * @return {Object}
 */
export default function mapRoutes(routes, basePath = '') {
  const map = {};

  for (const routeName in routes) {
    const route = routes[routeName];

    if ('resolver' in route) {
      map[basePath + route.path] = route.resolver;
    } else if ('component' in route) {
      const resolverClass = 'resolverClass' in route ? route.resolverClass : DefaultResolver;
      map[basePath + route.path] = new resolverClass(route.component, routeName);
    } else {
      throw new Error(`Either a resolver or a component must be provided for the route [${routeName}]`);
    }
  }

  return map;
}
