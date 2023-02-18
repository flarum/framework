import ItemList from './utils/ItemList';
import Translator from './Translator';
import Store, { ApiPayload, ApiResponsePlural, ApiResponseSingle } from './Store';
import Session from './Session';
import Drawer from './utils/Drawer';
import RequestError, { InternalFlarumRequestOptions } from './utils/RequestError';
import Forum from './models/Forum';
import PageState from './states/PageState';
import ModalManagerState from './states/ModalManagerState';
import AlertManagerState from './states/AlertManagerState';
import type DefaultResolver from './resolvers/DefaultResolver';
import type Mithril from 'mithril';
import type Component from './Component';
import type { ComponentAttrs } from './Component';
import Model, { SavedModelData } from './Model';
import IHistory from './IHistory';
import IExtender from './extenders/IExtender';
export declare type FlarumScreens = 'phone' | 'tablet' | 'desktop' | 'desktop-hd';
export declare type FlarumGenericRoute = RouteItem<any, any, any>;
export interface FlarumRequestOptions<ResponseType> extends Omit<Mithril.RequestOptions<ResponseType>, 'extract'> {
    errorHandler?: (error: RequestError) => void;
    url: string;
    /**
     * Manipulate the response text before it is parsed into JSON.
     *
     * @deprecated Please use `modifyText` instead.
     */
    extract?: (responseText: string) => string;
    /**
     * Manipulate the response text before it is parsed into JSON.
     *
     * This overrides any `extract` method provided.
     */
    modifyText?: (responseText: string) => string;
}
/**
 * A valid route definition.
 */
export declare type RouteItem<Attrs extends ComponentAttrs, Comp extends Component<Attrs & {
    routeName: string;
}>, RouteArgs extends Record<string, unknown> = {}> = {
    /**
     * The path for your route.
     *
     * This might be a specific URL path (e.g.,`/myPage`), or it might
     * contain a variable used by a resolver (e.g., `/myPage/:id`).
     *
     * @see https://docs.flarum.org/extend/frontend-pages.html#route-resolvers-advanced
     */
    path: `/${string}`;
} & ({
    /**
     * The component to render when this route matches.
     */
    component: new () => Comp;
    /**
     * A custom resolver class.
     *
     * This should be the class itself, and **not** an instance of the
     * class.
     */
    resolverClass?: new (component: new () => Comp, routeName: string) => DefaultResolver<Attrs, Comp, RouteArgs>;
} | {
    /**
     * An instance of a route resolver.
     */
    resolver: RouteResolver<Attrs, Comp, RouteArgs>;
});
export interface RouteResolver<Attrs extends ComponentAttrs, Comp extends Component<Attrs & {
    routeName: string;
}>, RouteArgs extends Record<string, unknown> = {}> {
    /**
     * A method which selects which component to render based on
     * conditional logic.
     *
     * Returns the component class, and **not** a Vnode or JSX
     * expression.
     *
     * @see https://mithril.js.org/route.html#routeresolveronmatch
     */
    onmatch(this: this, args: RouteArgs, requestedPath: string, route: string): {
        new (): Comp;
    };
    /**
     * A function which renders the provided component.
     *
     * If not specified, the route will default to rendering the
     * component on its own, inside of a fragment.
     *
     * Returns a Mithril Vnode or other children.
     *
     * @see https://mithril.js.org/route.html#routeresolverrender
     */
    render?(this: this, vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children;
}
export interface ApplicationData {
    apiDocument: ApiPayload | null;
    locale: string;
    locales: Record<string, string>;
    resources: SavedModelData[];
    session: {
        userId: number;
        csrfToken: string;
    };
    [key: string]: unknown;
}
/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
    /**
     * The forum model for this application.
     */
    forum: Forum;
    /**
     * A map of routes, keyed by a unique route name. Each route is an object
     * containing the following properties:
     *
     * - `path` The path that the route is accessed at.
     * - `component` The Mithril component to render when this route is active.
     *
     * @example
     * app.routes.discussion = { path: '/d/:id', component: DiscussionPage };
     */
    routes: Record<string, FlarumGenericRoute>;
    /**
     * An ordered list of initializers to bootstrap the application.
     */
    initializers: ItemList<(app: this) => void>;
    /**
     * The app's session.
     *
     * Stores info about the current user.
     */
    session: Session;
    /**
     * The app's translator.
     */
    translator: Translator;
    /**
     * The app's data store.
     */
    store: Store;
    /**
     * A local cache that can be used to store data at the application level, so
     * that is persists between different routes.
     */
    cache: Record<string, unknown>;
    /**
     * Whether or not the app has been booted.
     */
    booted: boolean;
    /**
     * The page the app is currently on.
     *
     * This object holds information about the type of page we are currently
     * visiting, and sometimes additional arbitrary page state that may be
     * relevant to lower-level components.
     */
    current: PageState;
    /**
     * The page the app was on before the current page.
     *
     * Once the application navigates to another page, the object previously
     * assigned to this.current will be moved to this.previous, while this.current
     * is re-initialized.
     */
    previous: PageState;
    /**
     * An object that manages modal state.
     */
    modal: ModalManagerState;
    /**
     * An object that manages the state of active alerts.
     */
    alerts: AlertManagerState;
    /**
     * An object that manages the state of the navigation drawer.
     */
    drawer: Drawer;
    history: IHistory | null;
    pane: any;
    data: ApplicationData;
    private _title;
    private _titleCount;
    private set title(value);
    get title(): string;
    private set titleCount(value);
    get titleCount(): number;
    /**
     * The key for an Alert that was shown as a result of an AJAX request error.
     * If present, it will be dismissed on the next successful request.
     */
    private requestErrorAlert;
    initialRoute: string;
    load(payload: Application['data']): void;
    boot(): void;
    bootExtensions(extensions: Record<string, {
        extend?: IExtender[];
    }>): void;
    protected mount(basePath?: string): void;
    /**
     * Get the API response document that has been preloaded into the application.
     */
    preloadedApiDocument<M extends Model>(): ApiResponseSingle<M> | null;
    preloadedApiDocument<Ms extends Model[]>(): ApiResponsePlural<Ms[number]> | null;
    /**
     * Determine the current screen mode, based on our media queries.
     */
    screen(): FlarumScreens;
    /**
     * Set the `<title>` of the page.
     *
     * @param title New page title
     */
    setTitle(title: string): void;
    /**
     * Set a number to display in the `<title>` of the page.
     *
     * @param count Number to display in title
     */
    setTitleCount(count: number): void;
    updateTitle(): void;
    protected transformRequestOptions<ResponseType>(flarumOptions: FlarumRequestOptions<ResponseType>): InternalFlarumRequestOptions<ResponseType>;
    /**
     * Make an AJAX request, handling any low-level errors that may occur.
     *
     * @see https://mithril.js.org/request.html
     */
    request<ResponseType>(originalOptions: FlarumRequestOptions<ResponseType>): Promise<ResponseType>;
    /**
     * By default, show an error alert, and log the error to the console.
     */
    protected requestErrorCatch<ResponseType>(error: RequestError, customErrorHandler: FlarumRequestOptions<ResponseType>['errorHandler']): Promise<never>;
    /**
     * Used to modify the error message shown on the page to help troubleshooting.
     * While not certain, a failing cross-origin request likely indicates a missing redirect to Flarum canonical URL.
     * Because XHR errors do not expose CORS information, we can only compare the requested URL origin to the page origin.
     *
     * @param error
     * @protected
     */
    protected requestWasCrossOrigin(error: RequestError): boolean;
    protected requestErrorDefaultHandler(e: unknown, isDebug: boolean, formattedErrors: string[]): void;
    private showDebug;
    /**
     * Construct a URL to the route with the given name.
     */
    route(name: string, params?: Record<string, unknown>): string;
}
