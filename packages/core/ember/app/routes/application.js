import Ember from 'ember';
import ApplicationRouteMixin from 'simple-auth/mixins/application-route-mixin';

import AlertMessage from 'flarum/components/ui/alert-message';

export default Ember.Route.extend(ApplicationRouteMixin, {
  activate: function() {
    if (!Ember.isEmpty(FLARUM_ALERT)) {
      this.controllerFor('alerts').send('alert', AlertMessage.create(FLARUM_ALERT));
      FLARUM_ALERT = null;
    }

    var restoreUrl = localStorage.getItem('restoreUrl');
    if (restoreUrl && this.get('session.isAuthenticated')) {
      this.transitionTo(restoreUrl);
      localStorage.removeItem('restoreUrl');
    }
  },

  actions: {
    login: function() {
      this.controllerFor('login').set('error', null);
      this.send('showModal', 'login');
    },

    signup: function() {
      this.controllerFor('signup').set('error', null).set('welcomeUser', null);
      this.send('showModal', 'signup');
    },

    showModal: function(name) {
      this.render(name, {
        into: 'application',
        outlet: 'modal'
      });
      this.controllerFor('application').set('modalController', this.controllerFor(name));
    },

    closeModal: function() {
      this.controllerFor('application').set('modalController', null);
    },

    destroyModal: function() {
      this.disconnectOutlet({
        outlet: 'modal',
        parentView: 'application'
      });
    },

    sessionChanged: function() {
      this.refresh();
    },

    saveState: function() {
      localStorage.setItem('restoreUrl', this.router.get('url'));
    }
  }
});
