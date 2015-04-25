import ScrollListener from 'flarum/utils/scroll-listener';
import History from 'flarum/utils/history';
import Pane from 'flarum/utils/pane';
import mapRoutes from 'flarum/utils/map-routes';

import BackButton from 'flarum/components/back-button';
import HeaderPrimary from 'flarum/components/header-primary';
import HeaderSecondary from 'flarum/components/header-secondary';
import FooterPrimary from 'flarum/components/footer-primary';
import FooterSecondary from 'flarum/components/footer-secondary';
import Composer from 'flarum/components/composer';
import Modal from 'flarum/components/modal';
import Alerts from 'flarum/components/alerts';
import SignupModal from 'flarum/components/signup-modal';
import LoginModal from 'flarum/components/login-modal';

export default function(app) {
  var id = id => document.getElementById(id);

  app.history = new History();
  app.pane = new Pane(id('page'));
  app.cache = {};

  app.signup = () => app.modal.show(new SignupModal());
  app.login = () => app.modal.show(new LoginModal());

  m.mount(id('back-control'), BackButton.component({ className: 'back-control', drawer: true }));
  m.mount(id('back-button'), BackButton.component());

  m.mount(id('header-primary'), HeaderPrimary.component());
  m.mount(id('header-secondary'), HeaderSecondary.component());
  m.mount(id('footer-primary'), FooterPrimary.component());
  m.mount(id('footer-secondary'), FooterSecondary.component());

  app.composer = m.mount(id('composer'), Composer.component());
  app.modal = m.mount(id('modal'), Modal.component());
  app.alerts = m.mount(id('alerts'), Alerts.component());

  m.route.mode = 'hash';
  m.route(id('content'), '/', mapRoutes(app.routes));

  new ScrollListener(top => $('body').toggleClass('scrolled', top > 0)).start();
}
