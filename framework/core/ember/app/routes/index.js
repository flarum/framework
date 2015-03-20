import Ember from 'ember';

export default Ember.Route.extend({
  deactivate: function() {
    this.controllerFor('application').set('backButtonTarget', null);
  }
});
