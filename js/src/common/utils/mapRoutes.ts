import { RouteDefs } from 'mithril';

/**
 * The `mapRoutes` utility converts a map of named application routes into a
 * format that can be understood by Mithril.
 *
 * @see https://lhorie.github.io/mithril/mithril.route.html#defining-routes
 */
export default function mapRoutes(routes: object, basePath: string = ''): RouteDefs {
    const map = {};

    for (const name in routes) {
        if (!routes.hasOwnProperty(name)) continue;

        const route = routes[name];

        map[basePath + route.path] = {
            view(vnode) {
                return m(route.component, { routeName: name, ...vnode.attrs });
            },
        };
    }

    return map;
}
