import Ember from 'ember';
import ApplicationRouteMixin from 'simple-auth/mixins/application-route-mixin';

export default Ember.Route.extend(ApplicationRouteMixin, {
  actions: {
    login: function() {
      this.controllerFor('login').set('error', null);
      this.send('showModal', 'login');
    },

    signup: function() {
      this.controllerFor('signup').set('error', null);
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
    }
  }
});
