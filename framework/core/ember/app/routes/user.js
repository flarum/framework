import Ember from 'ember';

export default Ember.Route.extend({
  model: function(params) {
    return this.store.find('user', params.username);
  },

  afterModel: function(model) {
    if (!model.get('joinTime')) {
      return model.reload();
    }
  }
});
