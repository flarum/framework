import Application from '../common/Application';
import History from './utils/History';
import Pane from './utils/Pane';

import Navigation from '../common/components/Navigation';
import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';
import Page from './components/Page';
import CommentPost from './components/CommentPost';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';

import { DiscussionListState } from './states/DiscussionListState';

import Notification from '../common/models/Notification';

import routes from './routes';

export default class Forum extends Application {
    /**
     * The app's history stack, which keeps track of which routes the user visits
     * so that they can easily navigate back to the previous route.
     */
    history: History = new History();

    /**
     * {@inheritdoc}
     */
    cache: {
        notifications?: Notification[][];
        discussionList?: DiscussionListState | null;
        [key: string]: any;
    } = {};

    postComponents = {
        comment: CommentPost,
        discussionRenamed: DiscussionRenamedPost,
    };

    previous?: Page;
    current?: Page;

    pane!: Pane;

    constructor() {
        super();

        routes(this);
    }

    mount() {
        // Get the configured default route and update that route's path to be '/'.
        // Push the homepage as the first route, so that the user will always be
        // able to click on the 'back' button to go home, regardless of which page
        // they started on.
        const defaultRoute = this.forum.attribute('defaultRoute');
        let defaultAction = 'index';

        for (const i in this.routes) {
            if (this.routes[i].path === defaultRoute) defaultAction = i;
        }

        this.routes[defaultAction].path = '/';
        this.history.push(defaultAction, this.translator.transText('core.forum.header.back_to_index_tooltip'), '/');

        m.mount(document.getElementById('app-navigation'), new Navigation({ className: 'App-backControl', drawer: true }));
        m.mount(document.getElementById('header-navigation'), new Navigation());
        m.mount(document.getElementById('header-primary'), new HeaderPrimary());
        m.mount(document.getElementById('header-secondary'), new HeaderSecondary());

        this.pane = new Pane(document.getElementById('app'));
        // this.composer = m.mount(document.getElementById('composer'), Composer.component());

        m.route.prefix = '';
        super.mount(this.forum.attribute('basePath'));

        // alertEmailConfirmation(this);

        // Route the home link back home when clicked. We do not want it to register
        // if the user is opening it in a new tab, however.
        $('#home-link').click((e: MouseEvent) => {
            if (e.ctrlKey || e.metaKey || e.which === 2) return;
            e.preventDefault();
            this.history.home();

            // Reload the current user so that their unread notification count is refreshed.
            if (this.session.user) {
                this.store.find('users', this.session.user.id());
                m.redraw();
            }
        });
    }
}
