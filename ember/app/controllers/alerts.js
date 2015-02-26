import Ember from 'ember';

export default Ember.Controller.extend({
  alerts: [],

  actions: {
    alert: function(message) {
      this.get('alerts').pushObject(message);
    },
    dismissAlert: function(message) {
      this.get('alerts').removeObject(message.constructor);
    },
    clearAlerts: function() {
      this.get('alerts').clear();
    }
  }
});
