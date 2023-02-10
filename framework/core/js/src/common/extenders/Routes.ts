import Application, { FlarumGenericRoute } from '../Application';
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
   */
  add(name: string, path: `/${string}`, component: any): Routes {
    this.routes[name] = { path, component };

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
