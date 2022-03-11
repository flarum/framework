import type { FlarumGenericRoute, RouteResolver } from '../Application';
import type Component from '../Component';
/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril, and wraps them in route resolvers
 * to provide each route with the current route name.
 *
 * @see https://mithril.js.org/route.html#signature
 */
export default function mapRoutes(routes: Record<string, FlarumGenericRoute>, basePath?: string): Record<string, RouteResolver<Record<string, unknown>, Component<{
    [key: string]: unknown;
    routeName: string;
}, undefined>, Record<string, unknown>>>;
