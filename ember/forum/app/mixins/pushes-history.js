import Ember from 'ember';

export default Ember.Mixin.create({
  pushHistory: function() {
    Ember.run.next(this, function() {
      this.controllerFor('application').pushHistory(this.get('historyKey'), this.get('url'));
    });
  },

  setupController: function(controller, model) {
    this._super(controller, model);
    this.pushHistory();
  },

  actions: {
    queryParamsDidChange: function() {
      this.pushHistory();
    }
  }
})
