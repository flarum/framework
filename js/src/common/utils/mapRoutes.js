/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://mithril.js.org/route.html#signature
 * @param {Object} routes
 * @param {String} [basePath]
 * @return {Object}
 */
export default function mapRoutes(routes, basePath = '') {
  const map = {};

  for (const key in routes) {
    const route = routes[key];
    const attrs = { routeName: key };
    const diffRoute = route.diffRoute;

    map[basePath + route.path] = diffRoute
      ? {
          render() {
            return m(route.component, attrs);
          },
        }
      : {
          view() {
            return m(route.component, attrs);
          },
        };
  }

  return map;
}
