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
import SearchManager from './SearchManager';
import { ColorScheme } from './components/ThemeMode';
export type FlarumScreens = 'phone' | 'tablet' | 'desktop' | 'desktop-hd';
export type FlarumGenericRoute = RouteItem<any, any, any>;
export interface FlarumRequestOptions<ResponseType> extends Omit<Mithril.RequestOptions<ResponseType>, 'extract'> {
    /**
     * Custom error handler for failed requests. Overrides the default error handler.
     *
     * Return `false` (sync or promise) to fall back to the default error handler.
     * Useful for handling specific errors without displaying error alerts to the user.
     * To always show alerts, catch the error rejected by `app.request` instead.
     *
     * @param error
     * @return  `false` (sync or promise) to fall back to the default error handler.
     */
    errorHandler?: (error: RequestError) => void | false | Promise<void | false>;
    url: string;
    /**
     * Manipulate the response text before it is parsed into JSON.
     *
     * This overrides any `extract` method provided.
     */
    modifyText?: (responseText: string) => string;
}
export type NewComponent<Comp> = new () => Comp;
export type AsyncNewComponent<Comp> = () => Promise<any & {
    default: NewComponent<Comp>;
}>;
/**
 * A valid route definition.
 */
export type RouteItem<Attrs extends ComponentAttrs, Comp extends Component<Attrs & {
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
    component: NewComponent<Comp> | AsyncNewComponent<Comp>;
    /**
     * A custom resolver class.
     *
     * This should be the class itself, and **not** an instance of the
     * class.
     */
    resolverClass?: new (component: NewComponent<Comp> | AsyncNewComponent<Comp>, routeName: string) => DefaultResolver<Attrs, Comp, RouteArgs>;
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
    onmatch(this: this, args: RouteArgs, requestedPath: string, route: string): Promise<{
        new (): Comp;
    }>;
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
export declare enum MaintenanceMode {
    NO_MAINTENANCE = "none",
    HIGH_MAINTENANCE = "high",
    LOW_MAINTENANCE = "low",
    SAFE_MODE = "safe"
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
    /** Token representing the compiled asset revisions the page booted with. */
    assetsRevision?: string;
    maintenanceMode?: MaintenanceMode;
    bisecting?: boolean;
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
     * An ordered list of chunk loaders to prefetch once the app is idle after
     * booting.
     *
     * Lazily-loaded (code-split) components add a network round-trip the first
     * time their route is visited. Registering their loader here warms the chunk
     * in the background so that, by the time the user navigates to it, the
     * dynamic import resolves from cache instead of blocking on a request.
     *
     * Each item is a thunk that triggers the load — a function returning the
     * dynamic import of the chunk (the same loader passed to a lazy route).
     */
    prefetch: ItemList<() => Promise<unknown>>;
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
    search: SearchManager;
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
    allowUserColorScheme: boolean;
    refs: Record<string, string>;
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
    /**
     * The key for the Alert that was shown as a result of an AJAX request
     * failing at the network level (status 0). Unlike other request error
     * alerts, only one of these is shown at a time.
     */
    protected networkErrorAlert: number | null;
    /**
     * The key for the Alert that is shown while the browser reports being
     * offline.
     */
    protected offlineAlert: number | null;
    /**
     * GET requests that failed because the browser was offline, keyed by
     * method, URL and params. They are retried, and their original promises
     * settled, once connectivity is restored. Identical requests (e.g. from a
     * polling extension) share one entry and are retried only once.
     */
    protected deferredRequests: Map<string, {
        options: FlarumRequestOptions<any>;
        settlers: {
            resolve: (value: any) => void;
            reject: (error: unknown) => void;
        }[];
    }>;
    initialRoute: string;
    /**
     * @internal
     */
    currentInitializerExtension: string | null;
    private handledErrors;
    private beforeMounts;
    load(payload: Application['data']): void;
    protected initialize(): CallableFunction[];
    boot(): void;
    /**
     * When the icons come from a Kit or CDN, they hold a blank placeholder font
     * until the remote stylesheet arrives. The backend wires a rescue — rebinding
     * the icons to the bundled fonts — to `onerror` on the remote tags, but some
     * failures never produce an error event: a kit script that loads fine while
     * its own stylesheet request is blocked, or a connection that hangs rather
     * than dying. This watchdog covers those. Firing by mistake is harmless: the
     * remote faces are declared later than the rescued ones whenever they do turn
     * up, so the icons simply upgrade in place.
     */
    protected watchIconFonts(): void;
    beforeMount(callback: () => void): void;
    protected runBeforeMount(): void;
    bootExtensions(extensions: Record<string, {
        extend?: IExtender[];
    }>): void;
    protected mount(basePath?: string): void;
    private initColorScheme;
    getSystemColorSchemePreference(): ColorScheme | string;
    watchSystemColorSchemePreference(callback: () => void): void;
    setColorScheme(scheme: ColorScheme | string): void;
    setColoredHeader(value: boolean): void;
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
     * Compare the asset revision reported by the server (on every API response) with
     * the one the page booted with. If they differ, the forum's JS/CSS has been
     * rebuilt since this page loaded.
     *
     * No-op in the base application; the forum app overrides this to prompt the user
     * to reload. The admin app intentionally does not.
     *
     * @param _serverRevision The asset revision reported by the server, or null.
     */
    checkAssetsRevision(_serverRevision: string | null): void;
    /**
     * Make an AJAX request, handling any low-level errors that may occur.
     *
     * @see https://mithril.js.org/request.html
     */
    request<ResponseType>(originalOptions: FlarumRequestOptions<ResponseType>): Promise<ResponseType>;
    /**
     * Whether a failed request should be held back and retried once
     * connectivity is restored, instead of being rejected.
     *
     * Only GET requests that failed at the network level while the browser
     * reported being offline qualify: they are safe to repeat, and the
     * `online` event provides a reliable signal to retry them.
     */
    protected shouldDeferRequest(error: unknown, originalOptions: FlarumRequestOptions<any>): boolean;
    /**
     * Hold a request that failed while offline. The returned promise settles
     * with the result of retrying the request once connectivity is restored.
     */
    protected deferRequest<ResponseType>(originalOptions: FlarumRequestOptions<ResponseType>): Promise<ResponseType>;
    /**
     * The identity of a request for deferral purposes: requests with the same
     * key are considered identical and are retried only once.
     */
    protected deferredRequestKey(options: FlarumRequestOptions<any>): string;
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
    /**
     * Whether an alert about a connection problem (a network-level request
     * failure or the browser being offline) is currently being shown.
     */
    protected connectionAlertActive(): boolean;
    /**
     * Register listeners to proactively notify the user when the browser goes
     * offline and when connectivity is restored.
     */
    protected registerConnectivityListeners(): void;
    /**
     * Show a persistent (but dismissible) alert while the browser reports being
     * offline.
     */
    protected connectionLost(): void;
    /**
     * Dismiss any connection problem alerts, retry requests that were deferred
     * while offline, and briefly confirm to the user that connectivity has
     * been restored.
     */
    protected connectionRestored(): void;
    private showDebug;
    /**
     * Construct a URL to the route with the given name.
     */
    route(name: string, params?: Record<string, unknown>): string;
    handleErrorOnce(extension: null | string, errorId: string, userTitle: string, consoleTitle: string, error: any): void;
}
