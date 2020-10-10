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
  let keyModifier = '';

  for (const routeName in routes) {
    const route = routes[routeName];

    map[basePath + route.path] = {
      onmatch(args, requestedPath, route) {
        if (args.forceRefresh || app.forceRefresh) {
          keyModifier = +new Date();
          app.forceRefresh = false;
        }
      },
      render() {
        const key = routeName + JSON.stringify(m.route.param()) + keyModifier;
        return [m(route.component, { routeName, key })];
      },
    };
  }

  return map;
}
