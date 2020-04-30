import Mithril from 'mithril';

import Translator from './Translator';
import Session from './Session';
import Store from './Store';
import { extend } from './extend';

import extract from './utils/extract';
import mapRoutes from './utils/mapRoutes';
import Drawer from './utils/Drawer';
import RequestError from './utils/RequestError';
import ItemList from './utils/ItemList';
import ScrollListener from './utils/ScrollListener';

import Forum from './models/Forum';
import Discussion from './models/Discussion';
import User from './models/User';
import Post from './models/Post';
import Group from './models/Group';
import Notification from './models/Notification';

import Alert from './components/Alert';
import Button from './components/Button';
import ModalManager from './components/ModalManager';
import RequestErrorModal from './components/RequestErrorModal';

import flattenDeep from 'lodash/flattenDeep';
import AlertManager from './components/AlertManager';

export type ApplicationData = {
    apiDocument: any;
    locale: string;
    locales: any;
    resources: any[];
    session: any;
};

export default abstract class Application {
    /**
     * The forum model for this application.
     */
    public forum!: Forum;

    /**
     * A map of routes, keyed by a unique route name. Each route is an object
     * containing the following properties:
     *
     * - `path` The path that the route is accessed at.
     * - `component` The Mithril component to render when this route is active.
     *
     * @example
     * app.routes.discussion = {path: '/d/:id', component: DiscussionPage.component()};
     */
    public routes: { [key: string]: { path: string; component: any; [key: string]: any } } = {};

    /**
     * An ordered list of initializers to bootstrap the application.
     */
    public initializers = new ItemList();

    /**
     * The app's session.
     */
    public session!: Session;

    /**
     * The app's translator.
     */
    public translator = new Translator();

    /**
     * The app's data store.
     */
    public store = new Store({
        forums: Forum,
        users: User,
        discussions: Discussion,
        posts: Post,
        groups: Group,
        notifications: Notification,
    });

    /**
     * A local cache that can be used to store data at the application level, so
     * that is persists between different routes.
     */
    public cache: { [key: string]: any } = {};

    /**
     * Whether or not the app has been booted.
     */
    public booted: boolean = false;

    /**
     * An Alert that was shown as a result of an AJAX request error. If present,
     * it will be dismissed on the next successful request.
     */
    private requestError: RequestError | null = null;

    data!: ApplicationData;

    title = '';
    titleCount = 0;

    drawer = new Drawer();

    modal!: ModalManager;
    alerts!: AlertManager;

    load(payload) {
        this.data = payload;
        this.translator.locale = payload.locale;
    }

    boot() {
        //this.initializers.toArray().forEach((initializer) => initializer(this));

        this.store.pushPayload({ data: this.data.resources });

        this.forum = this.store.getById('forums', 1);

        this.session = new Session(this.store.getById('users', this.data.session.userId), this.data.session.csrfToken);

        this.mount();

        this.booted = true;
    }

    bootExtensions(extensions) {
        Object.keys(extensions).forEach((name) => {
            const extension = extensions[name];

            const extenders = flattenDeep(extension.extend);

            for (const extender of extenders) {
                extender.extend(this, { name, exports: extension });
            }
        });
    }

    mount(basePath = '') {
        const $modal = document.getElementById('modal');
        const $alerts = document.getElementById('alerts');
        const $content = document.getElementById('content');

        if ($modal) m.mount($modal, (this.modal = new ModalManager()));

        if ($alerts) m.mount($alerts, (this.alerts = new AlertManager({ oninit: (vnode) => (this.alerts = vnode.state) })));

        if ($content) m.route($content, basePath + '/', mapRoutes(this.routes, basePath));

        // Add a class to the body which indicates that the page has been scrolled
        // down.
        new ScrollListener((top) => {
            const $app = $('#app');
            const offset = $app.offset().top;

            $app.toggleClass('affix', top >= offset).toggleClass('scrolled', top > offset);
        }).start();

        $(() => {
            $('body').addClass('ontouchstart' in window ? 'touch' : 'no-touch');
        });
    }

    /**
     * Get the API response document that has been preloaded into the application.
     */
    preloadedApiDocument() {
        if (this.data.apiDocument) {
            const results = this.store.pushPayload(this.data.apiDocument);

            this.data.apiDocument = null;

            return results;
        }

        return null;
    }

    /**
     * Set the <title> of the page.
     */
    setTitle(title: string) {
        this.title = title;
        this.updateTitle();
    }

    /**
     * Set a number to display in the <title> of the page.
     */
    setTitleCount(count: number) {
        this.titleCount = count;
        this.updateTitle();
    }

    updateTitle() {
        document.title = (this.titleCount ? `(${this.titleCount}) ` : '') + (this.title ? this.title + ' - ' : '') + this.forum.attribute('title');
    }

    /**
     * Construct a URL to the route with the given name.
     */
    route(name: string, params: object = {}): string {
        const route = this.routes[name];

        if (!route) throw new Error(`Route '${name}' does not exist`);

        const url = route.path.replace(/:([^\/]+)/g, (m, key) => extract(params, key));

        // Remove falsy values in params to avoid
        // having urls like '/?sort&q'
        for (const key in params) {
            if (params.hasOwnProperty(key) && !params[key]) delete params[key];
        }

        const queryString = m.buildQueryString(params as Mithril.Params);
        const prefix = m.route.prefix === '' ? this.forum.attribute('basePath') : '';

        return prefix + url + (queryString ? '?' + queryString : '');
    }

    /**
     * Make an AJAX request, handling any low-level errors that may occur.
     *
     * @see https://mithril.js.org/request.html
     */
    request(originalOptions: Mithril.RequestOptions<JSON> | any): Promise<any> {
        const options: Mithril.RequestOptions<JSON> | any = Object.assign({}, originalOptions);

        // Set some default options if they haven't been overridden. We want to
        // authenticate all requests with the session token. We also want all
        // requests to run asynchronously in the background, so that they don't
        // prevent redraws from occurring.
        options.background = options.background || true;

        extend(options, 'config', (result, xhr: XMLHttpRequest) => xhr.setRequestHeader('X-CSRF-Token', this.session.csrfToken!));

        // If the method is something like PATCH or DELETE, which not all servers
        // and clients support, then we'll send it as a POST request with the
        // intended method specified in the X-HTTP-Method-Override header.
        if (options.method !== 'GET' && options.method !== 'POST') {
            const method = options.method;
            extend(options, 'config', (result, xhr: XMLHttpRequest) => xhr.setRequestHeader('X-HTTP-Method-Override', method));
            options.method = 'POST';
        }

        // When we deserialize JSON data, if for some reason the server has provided
        // a dud response, we don't want the application to crash. We'll show an
        // error message to the user instead.
        options.deserialize = options.deserialize || ((responseText) => responseText);

        options.errorHandler =
            options.errorHandler ||
            ((error) => {
                throw error;
            });

        // When extracting the data from the response, we can check the server
        // response code and show an error message to the user if something's gone
        // awry.
        const original = options.extract;
        options.extract = (xhr) => {
            let responseText;

            if (original) {
                responseText = original(xhr.responseText);
            } else {
                responseText = xhr.responseText || null;
            }

            const status = xhr.status;

            if (status < 200 || status > 299) {
                throw new RequestError(status, responseText, options, xhr);
            }

            if (xhr.getResponseHeader) {
                const csrfToken = xhr.getResponseHeader('X-CSRF-Token');
                if (csrfToken) app.session.csrfToken = csrfToken;
            }

            try {
                return JSON.parse(responseText);
            } catch (e) {
                throw new RequestError(500, responseText, options, xhr);
            }
        };

        if (this.requestError) this.alerts.dismiss(this.requestError.alert);

        // Now make the request. If it's a failure, inspect the error that was
        // returned and show an alert containing its contents.
        return m.request(options).then(
            (res) => res,
            (error) => {
                this.requestError = error;

                let children;

                switch (error.status) {
                    case 422:
                        children = error.response.errors
                            .map((error) => [error.detail, m('br')])
                            .reduce((a, b) => a.concat(b), [])
                            .slice(0, -1);
                        break;

                    case 401:
                    case 403:
                        children = this.translator.trans('core.lib.error.permission_denied_message');
                        break;

                    case 404:
                    case 410:
                        children = this.translator.trans('core.lib.error.not_found_message');
                        break;

                    case 429:
                        children = this.translator.trans('core.lib.error.rate_limit_exceeded_message');
                        break;

                    default:
                        children = this.translator.trans('core.lib.error.generic_message');
                }

                const isDebug = app.forum.attribute('debug');

                error.alert = Alert.component({
                    type: 'error',
                    children,
                    controls: isDebug && [
                        Button.component({
                            className: 'Button Button--link',
                            onclick: this.showDebug.bind(this, error),
                            children: 'DEBUG', // TODO make translatable
                        }),
                    ],
                });

                try {
                    options.errorHandler(error);
                } catch (error) {
                    console.error(error);
                    this.alerts.show(error.alert);
                }

                return Promise.reject(error);
            }
        );
    }

    private showDebug(error: RequestError) {
        this.alerts.dismiss(this.requestError!.alert);

        this.modal.show(RequestErrorModal, { error });
    }
}
