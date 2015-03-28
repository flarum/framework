import Ember from 'ember';

export default Ember.Route.extend({
  model: function() {
    return Ember.RSVP.resolve(Ember.ArrayProxy.create());
  },

  setupController: function(controller, model) {
    this._super(controller, model);

    controller.send('loadResults');
  }
});
