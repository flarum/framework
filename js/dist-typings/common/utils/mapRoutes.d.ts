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
export default function mapRoutes(routes: Object, basePath?: string | undefined): Object;
