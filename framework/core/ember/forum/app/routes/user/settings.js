import Ember from 'ember';

export default Ember.Route.extend({
  model: function() {
    return Ember.RSVP.resolve(this.modelFor('user'));
  }
});
