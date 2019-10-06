import { RouteDefs } from 'mithril';

/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://lhorie.github.io/mithril/mithril.route.html#defining-routes
 */
export default function mapRoutes(routes: object, basePath: string = ''): RouteDefs {
    const map = {};

    for (const key in routes) {
        const route = routes[key];

        if (route.component) {
            if (!route.component.attrs) route.component.attrs = {};
            route.component.attrs.routeName = key;
        }

        map[basePath + route.path] = route.component;
    }

    return map;
}
