import * as mithril from 'mithril';

/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://mithril.js.org/route.html#signature
 * @param {mithril.Route} routes
 * @param {string} [basePath]
 * @return {Partial<mithril.Route>}
 */
export default function mapRoutes(routes: mithril.Route, basePath: string = ''): Partial<mithril.Route> {
  const map: Partial<mithril.Route> = {};

  for (const key in routes) {
    const route = routes[key];

    map[basePath + route.path] = {
      render() {
        return m(route.component, { routeName: key });
      },
    };
  }

  return map;
}
