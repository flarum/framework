import Application from '../common/Application';
import History from './utils/History';

import HeaderPrimary from './components/HeaderPrimary';
import HeaderSecondary from './components/HeaderSecondary';

import IndexPage from './components/IndexPage';
import PostsUserPage from './components/PostsUserPage';

export default class Forum extends Application {
    routes = {
        'index': { path: '/', component: new IndexPage() },
        'index.filter': { path: '/:filter', component: new IndexPage() },

        'user': { path: '/u/:username', component: new PostsUserPage() },
        'user.posts': { path: '/u/:username', component: new PostsUserPage() },
        'user.discussions': { path: '/u/:username', component: new PostsUserPage() },
        'settings': { path: '/u/:username', component: new PostsUserPage() },
    };

    /**
     * The app's history stack, which keeps track of which routes the user visits
     * so that they can easily navigate back to the previous route.
     */
    history: History = new History();

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

      // m.mount(document.getElementById('app-navigation'), Navigation.component({className: 'App-backControl', drawer: true}));
      // m.mount(document.getElementById('header-navigation'), Navigation.component());
      m.mount(document.getElementById('header-primary'), new HeaderPrimary());
      m.mount(document.getElementById('header-secondary'), new HeaderSecondary());

      // this.pane = new Pane(document.getElementById('app'));
      // this.composer = m.mount(document.getElementById('composer'), Composer.component());

      m.route.prefix = '';
      super.mount(this.forum.attribute('basePath'));

      // alertEmailConfirmation(this);

      // Route the home link back home when clicked. We do not want it to register
      // if the user is opening it in a new tab, however.
      $('#home-link').click((e: MouseEvent) => {
        if (e.ctrlKey || e.metaKey || e.which === 2) return;
        e.preventDefault();
        app.history.home();

        // Reload the current user so that their unread notification count is refreshed.
        if (app.session.user) {
          app.store.find('users', app.session.user.id());
          m.redraw();
        }
      });
    }

    setupRoutes() {
      super.setupRoutes();

      this.route.discussion = (discussion, near) => {
        const slug = discussion.slug();
        return this.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
          id: discussion.id() + (slug.trim() ? '-' + slug : ''),
          near: near && near !== 1 ? near : undefined
        });
      };

      /**
       * Generate a URL to a post.
       *
       * @param {Post} post
       * @return {String}
       */
      this.route.post = post => {
        return this.route.discussion(post.discussion(), post.number());
      };

      /**
       * Generate a URL to a user.
       *
       * @param {User} user
       * @return {String}
       */
      this.route.user = user => {
        return this.route('user', {
          username: user.username()
        });
      };
    }
}
