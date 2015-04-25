import ScrollListener from 'flarum/utils/scroll-listener';
import mapRoutes from 'flarum/utils/map-routes';

import BackButton from 'flarum/components/back-button';
import HeaderPrimary from 'flarum/components/header-primary';
import HeaderSecondary from 'flarum/components/header-secondary';
import Modal from 'flarum/components/modal';
import Alerts from 'flarum/components/alerts';
import AdminNav from 'flarum/components/admin-nav';

export default function(app) {
  var id = id => document.getElementById(id);

  app.history = {
    back: function() {
      window.location = 'http://flarum.dev';
    },
    canGoBack: function() {
      return true;
    }
  };

  m.mount(id('back-control'), BackButton.component({ className: 'back-control', drawer: true }));
  m.mount(id('back-button'), BackButton.component());

  m.mount(id('header-primary'), HeaderPrimary.component());
  m.mount(id('header-secondary'), HeaderSecondary.component());

  m.mount(id('admin-nav'), AdminNav.component());

  app.modal = m.mount(id('modal'), Modal.component());
  app.alerts = m.mount(id('alerts'), Alerts.component());

  m.route.mode = 'hash';
  m.route(id('content'), '/', mapRoutes(app.routes));

  new ScrollListener(top => $('body').toggleClass('scrolled', top > 0)).start();
}
