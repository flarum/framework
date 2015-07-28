/*global FastClick*/

import ScrollListener from 'flarum/utils/ScrollListener';
import Pane from 'flarum/utils/Pane';
import Drawer from 'flarum/utils/Drawer';
import mapRoutes from 'flarum/utils/mapRoutes';

import Navigation from 'flarum/components/Navigation';
import HeaderPrimary from 'flarum/components/HeaderPrimary';
import HeaderSecondary from 'flarum/components/HeaderSecondary';
import FooterPrimary from 'flarum/components/FooterPrimary';
import FooterSecondary from 'flarum/components/FooterSecondary';
import Composer from 'flarum/components/Composer';
import ModalManager from 'flarum/components/ModalManager';
import AlertManager from 'flarum/components/AlertManager';

/**
 * The `boot` initializer boots up the forum app. It initializes some app
 * globals, mounts components to the page, and begins routing.
 *
 * @param {ForumApp} app
 */
export default function boot(app) {
  m.startComputation();

  m.mount(document.getElementById('app-navigation'), Navigation.component({className: 'App-backControl', drawer: true}));
  m.mount(document.getElementById('header-navigation'), Navigation.component());
  m.mount(document.getElementById('header-primary'), HeaderPrimary.component());
  m.mount(document.getElementById('header-secondary'), HeaderSecondary.component());
  m.mount(document.getElementById('footer-primary'), FooterPrimary.component());
  m.mount(document.getElementById('footer-secondary'), FooterSecondary.component());

  app.pane = new Pane(document.getElementById('app'));
  app.drawer = new Drawer();
  app.composer = m.mount(document.getElementById('composer'), Composer.component());
  app.modal = m.mount(document.getElementById('modal'), ModalManager.component());
  app.alerts = m.mount(document.getElementById('alerts'), AlertManager.component());

  m.route.mode = 'pathname';
  m.route(document.getElementById('content'), '/', mapRoutes(app.routes));

  m.endComputation();

  // Route the home link back home when clicked. We do not want it to register
  // if the user is opening it in a new tab, however.
  $('#home-link').click(e => {
    if (e.ctrlKey || e.metaKey || e.which === 2) return;
    e.preventDefault();
    app.history.home();
  });

  // Add a class to the body which indicates that the page has been scrolled
  // down.
  new ScrollListener(top => {
    const $app = $('#app');
    const offset = $app.offset().top;

    $app
      .toggleClass('affix', top >= offset)
      .toggleClass('scrolled', top > offset);
  }).start();

  // Initialize FastClick, which makes links and buttons much more responsive on
  // touch devices.
  $(() => FastClick.attach(document.body));

  app.booted = true;
}
