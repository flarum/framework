import type Mithril from 'mithril';

/**
 * Mithril 2 does not completely rerender the page if a route change leads to the same route
 * (or the same component handling a different route). This util calls m.route.set, forcing a reonit.
 *
 * @see https://mithril.js.org/route.html#key-parameter
 */
export default function setRouteWithForcedRefresh(route: string, params = null, options: Mithril.RouteOptions = {}) {
  const newOptions = { ...options };
  newOptions.state = newOptions.state || {};
  newOptions.state.key = Date.now();

  m.route.set(route, params, newOptions);
}
