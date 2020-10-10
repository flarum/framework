import Mithril from 'mithril';

/**
 * Mithril 2 does not completely rerender the page if a route change
 * leads to the same route. This util calls m.route.set, forcing a reonit.
 * For pages that are handled by the same component, but have a different
 * route name, calling this function is not needed.
 *
 * @see https://mithril.js.org/route.html#key-parameter
 */
export default function setRouteWithForcedRefresh(route: string, params = null, options: Mithril.RouteOptions = {}) {
  app.forceRefresh = true;

  m.route.set(route, params, options);
}
