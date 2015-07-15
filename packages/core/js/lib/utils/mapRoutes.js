/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://lhorie.github.io/mithril/mithril.route.html#defining-routes
 * @param {Object} routes
 * @return {Object}
 */
export default function mapRoutes(routes) {
  const map = {};

  for (const key in routes) {
    const route = routes[key];

    if (route.component) route.component.props.routeName = key;

    map[route.path] = route.component;
  }

  return map;
}
