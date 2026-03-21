import type Application from '../Application';
import type { AsyncNewComponent, NewComponent } from '../Application';
import DefaultResolver from '../resolvers/DefaultResolver';
import IExtender, { IExtensionModule } from './IExtender';
type HelperRoute = (...args: any) => string;
export default class Routes implements IExtender {
    private routes;
    private helpers;
    /**
     * Add a mithril route to the application.
     *
     * @param name The name of the route.
     * @param path The path of the route.
     * @param component must extend `Page` component.
     * @param resolverClass An optional custom route resolver class.
     */
    add(name: string, path: `/${string}`, component: NewComponent<any> | AsyncNewComponent<any>, resolverClass?: typeof DefaultResolver): Routes;
    helper(name: string, callback: HelperRoute): Routes;
    extend(app: Application, extension: IExtensionModule): void;
}
export {};
