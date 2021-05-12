/**
 * The `App` class provides a container for an application, as well as various
 * utilities for the rest of the app to use.
 */
export default class Application {
    /**
     * The forum model for this application.
     *
     * @type {Forum}
     * @public
     */
    public forum: Forum;
    /**
     * A map of routes, keyed by a unique route name. Each route is an object
     * containing the following properties:
     *
     * - `path` The path that the route is accessed at.
     * - `component` The Mithril component to render when this route is active.
     *
     * @example
     * app.routes.discussion = {path: '/d/:id', component: DiscussionPage.component()};
     *
     * @type {Object}
     * @public
     */
    public routes: Object;
    /**
     * An ordered list of initializers to bootstrap the application.
     *
     * @type {ItemList}
     * @public
     */
    public initializers: ItemList;
    /**
     * The app's session.
     *
     * @type {Session}
     * @public
     */
    public session: Session;
    /**
     * The app's translator.
     *
     * @type {Translator}
     * @public
     */
    public translator: Translator;
    /**
     * The app's data store.
     *
     * @type {Store}
     * @public
     */
    public store: Store;
    /**
     * A local cache that can be used to store data at the application level, so
     * that is persists between different routes.
     *
     * @type {Object}
     * @public
     */
    public cache: Object;
    /**
     * Whether or not the app has been booted.
     *
     * @type {Boolean}
     * @public
     */
    public booted: boolean;
    /**
     * The key for an Alert that was shown as a result of an AJAX request error.
     * If present, it will be dismissed on the next successful request.
     *
     * @type {int}
     * @private
     */
    private requestErrorAlert;
    /**
     * The page the app is currently on.
     *
     * This object holds information about the type of page we are currently
     * visiting, and sometimes additional arbitrary page state that may be
     * relevant to lower-level components.
     *
     * @type {PageState}
     */
    current: PageState;
    /**
     * The page the app was on before the current page.
     *
     * Once the application navigates to another page, the object previously
     * assigned to this.current will be moved to this.previous, while this.current
     * is re-initialized.
     *
     * @type {PageState}
     */
    previous: PageState;
    modal: ModalManagerState;
    /**
     * An object that manages the state of active alerts.
     *
     * @type {AlertManagerState}
     */
    alerts: AlertManagerState;
    data: any;
    title: string;
    titleCount: number;
    initialRoute: any;
    load(payload: any): void;
    boot(): void;
    bootExtensions(extensions: any): void;
    mount(basePath?: string): void;
    drawer: Drawer | undefined;
    /**
     * Get the API response document that has been preloaded into the application.
     *
     * @return {Object|null}
     * @public
     */
    public preloadedApiDocument(): Object | null;
    /**
     * Determine the current screen mode, based on our media queries.
     *
     * @returns {String} - one of "phone", "tablet", "desktop" or "desktop-hd"
     */
    screen(): string;
    /**
     * Set the <title> of the page.
     *
     * @param {String} title
     * @public
     */
    public setTitle(title: string): void;
    /**
     * Set a number to display in the <title> of the page.
     *
     * @param {Integer} count
     */
    setTitleCount(count: any): void;
    updateTitle(): void;
    /**
     * Make an AJAX request, handling any low-level errors that may occur.
     *
     * @see https://mithril.js.org/request.html
     * @param {Object} options
     * @return {Promise}
     * @public
     */
    public request(originalOptions: any): Promise<any>;
    /**
     * @param {RequestError} error
     * @param {string[]} [formattedError]
     * @private
     */
    private showDebug;
    /**
     * Construct a URL to the route with the given name.
     *
     * @param {String} name
     * @param {Object} params
     * @return {String}
     * @public
     */
    public route(name: string, params?: Object): string;
}
import Forum from "./models/Forum";
import ItemList from "./utils/ItemList";
import Session from "./Session";
import Translator from "./Translator";
import Store from "./Store";
import PageState from "./states/PageState";
import ModalManagerState from "./states/ModalManagerState";
import AlertManagerState from "./states/AlertManagerState";
import Drawer from "./utils/Drawer";
