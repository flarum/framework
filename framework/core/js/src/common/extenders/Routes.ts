import type Application from '../Application';
import type { AsyncNewComponent, FlarumGenericRoute, NewComponent } from '../Application';
import DefaultResolver from '../resolvers/DefaultResolver';
import IExtender, { IExtensionModule } from './IExtender';

type HelperRoute = (...args: any) => string;

export default class Routes implements IExtender {
  private routes: Record<string, FlarumGenericRoute> = {};
  private helpers: Record<string, HelperRoute> = {};

  /**
   * Add a mithril route to the application.
   *
   * @param name The name of the route.
   * @param path The path of the route.
   * @param component must extend `Page` component.
   * @param resolverClass An optional custom route resolver class.
   *
   * `resolverClass` is spelled as a construct signature rather than as
   * `typeof DefaultResolver`, and has to be. A resolver that narrows the
   * component it accepts -- which is the only reason to write one, and what
   * both of core's own do -- is not assignable to `typeof DefaultResolver`,
   * because constructor parameters are contravariant and `DefaultResolver`'s
   * are open. This matches the `resolverClass` field on `RouteItem`, which is
   * where the value is being put.
   */
  add(
    name: string,
    path: `/${string}`,
    component: NewComponent<any> | AsyncNewComponent<any>,
    resolverClass?: new (component: NewComponent<any> | AsyncNewComponent<any>, routeName: string) => DefaultResolver<any, any, any>
  ): Routes {
    const route: FlarumGenericRoute = { path, component };

    if (resolverClass) {
      route.resolverClass = resolverClass;
    }

    this.routes[name] = route;

    return this;
  }

  helper(name: string, callback: HelperRoute): Routes {
    this.helpers[name] = callback;

    return this;
  }

  extend(app: Application, extension: IExtensionModule) {
    Object.assign(app.routes, this.routes);
    Object.assign(app.route, this.helpers);
  }
}
